<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PunchSession;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    public function index(Request $request)
    {
        $orgId      = auth()->user()->org_id;
        $date       = $request->query('date', today()->toDateString());
        $dateCarbon = Carbon::parse($date);

        $totalEmployees = User::where('org_id', $orgId)
            ->where('role', 'employee')
            ->where('is_active', true)
            ->count();

        $onTheClock = PunchSession::whereHas('user', fn($q) => $q->where('org_id', $orgId))
            ->whereIn('state', ['active', 'on_break'])
            ->whereDate('clocked_in_at', $date)
            ->count();

        $lateCount = PunchSession::whereHas('user', fn($q) => $q->where('org_id', $orgId))
            ->where('is_flagged', true)
            ->whereDate('clocked_in_at', $date)
            ->whereHas('events', fn($q) => $q->where('event_type', 'clock_in')->where('flag_reason', 'like', 'late%'))
            ->count();

        $noShowCount = Shift::where('org_id', $orgId)
            ->whereDate('scheduled_start', $date)
            ->whereDoesntHave('punchSessions')
            ->where('scheduled_start', '<', now()->subMinutes(30))
            ->count();

        $weekStart = $dateCarbon->copy()->startOfWeek();
        $weekEnd   = $dateCarbon->copy()->endOfWeek();

        $overtimeHoursWeek = round(
            PunchSession::whereHas('user', fn($q) => $q->where('org_id', $orgId))
                ->where('state', 'clocked_out')
                ->whereBetween('clocked_in_at', [$weekStart, $weekEnd])
                ->sum('overtime_seconds') / 3600,
            1
        );

        $needsAttention = Shift::where('org_id', $orgId)
            ->whereDate('scheduled_start', $date)
            ->whereDoesntHave('punchSessions')
            ->where('scheduled_start', '<', now()->subMinutes(30))
            ->with('user', 'site')
            ->limit(10)
            ->get()
            ->map(fn($shift) => [
                'name'  => $shift->user?->full_name ?? '—',
                'issue' => 'no-show · ' . ($shift->site?->name ?? '—'),
            ]);

        $dayLabels = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
        $hoursByDay = [];
        for ($i = 0; $i < 7; $i++) {
            $day   = $weekStart->copy()->addDays($i);
            $hours = PunchSession::whereHas('user', fn($q) => $q->where('org_id', $orgId))
                ->where('state', 'clocked_out')
                ->whereDate('clocked_in_at', $day->toDateString())
                ->sum('work_seconds') / 3600;
            $hoursByDay[] = ['day' => $dayLabels[$i], 'hours' => round($hours, 1)];
        }

        $maxHours = max(1, collect($hoursByDay)->max('hours'));

        return view('dashboard.overview', compact(
            'date', 'totalEmployees', 'onTheClock', 'lateCount',
            'noShowCount', 'overtimeHoursWeek', 'needsAttention',
            'hoursByDay', 'maxHours', 'weekStart', 'weekEnd'
        ));
    }
}

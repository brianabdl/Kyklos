<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\PunchSession;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $date   = $request->query('date', today()->toDateString());
        $orgId  = $request->org_id;
        $dateCarbon = \Carbon\Carbon::parse($date);

        $totalEmployees = User::where('org_id', $orgId)->where('role', 'employee')->where('is_active', true)->count();

        $onTheClock = PunchSession::whereHas('user', fn($q) => $q->where('org_id', $orgId))
            ->whereIn('state', ['active', 'on_break'])
            ->whereDate('clocked_in_at', $date)
            ->count();

        $lateCount = PunchSession::whereHas('user', fn($q) => $q->where('org_id', $orgId))
            ->where('is_flagged', true)
            ->whereDate('clocked_in_at', $date)
            ->whereHas('events', fn($q) => $q->where('event_type', 'clock_in')->where('flag_reason', 'like', 'late%'))
            ->count();

        // No-show: shifts today with no punch session
        $noShowCount = Shift::where('org_id', $orgId)
            ->whereDate('scheduled_start', $date)
            ->whereDoesntHave('punchSessions')
            ->where('scheduled_start', '<', now()->subMinutes(30))
            ->count();

        $weekStart = $dateCarbon->copy()->startOfWeek();
        $weekEnd   = $dateCarbon->copy()->endOfWeek();

        $overtimeHoursWeek = PunchSession::whereHas('user', fn($q) => $q->where('org_id', $orgId))
            ->where('state', 'clocked_out')
            ->whereBetween('clocked_in_at', [$weekStart, $weekEnd])
            ->sum('overtime_seconds') / 3600;

        // Needs attention: no-shows
        $needsAttention = Shift::where('org_id', $orgId)
            ->whereDate('scheduled_start', $date)
            ->whereDoesntHave('punchSessions')
            ->where('scheduled_start', '<', now()->subMinutes(30))
            ->with('user', 'site')
            ->limit(10)
            ->get()
            ->map(fn($shift) => [
                'userId' => $shift->user_id,
                'name'   => $shift->user->full_name,
                'issue'  => 'no-show · ' . $shift->site->name,
                'action' => 'follow up',
            ]);

        // Hours by day for the current week
        $hoursByDay = [];
        $dayLabels  = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
        for ($i = 0; $i < 7; $i++) {
            $day   = $weekStart->copy()->addDays($i);
            $hours = PunchSession::whereHas('user', fn($q) => $q->where('org_id', $orgId))
                ->where('state', 'clocked_out')
                ->whereDate('clocked_in_at', $day->toDateString())
                ->sum('work_seconds') / 3600;
            $hoursByDay[] = ['day' => $dayLabels[$i], 'hours' => round($hours, 1)];
        }

        return response()->json([
            'date'               => $date,
            'totalEmployees'     => $totalEmployees,
            'onTheClock'         => $onTheClock,
            'lateCount'          => $lateCount,
            'noShowCount'        => $noShowCount,
            'overtimeHoursWeek'  => round($overtimeHoursWeek, 1),
            'needsAttention'     => $needsAttention,
            'hoursByDay'         => $hoursByDay,
        ]);
    }
}

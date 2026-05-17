<?php

namespace App\Http\Controllers;

use App\Models\PunchSession;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function monthly(Request $request): JsonResponse
    {
        $month = $request->query('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $start = \Carbon\Carbon::create($year, $mon, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $user = $request->user();

        $sessions = PunchSession::where('user_id', $user->id)
            ->where('state', 'clocked_out')
            ->whereBetween('clocked_in_at', [$start, $end])
            ->get();

        $shiftsScheduled = Shift::where('user_id', $user->id)
            ->whereBetween('scheduled_start', [$start, $end])
            ->count();

        $hoursWorked     = round($sessions->sum('work_seconds') / 3600, 1);
        $overtimeHours   = round($sessions->sum('overtime_seconds') / 3600, 1);
        $shiftsCompleted = $sessions->count();
        $lateCount       = $sessions->filter(fn($s) => $s->is_flagged)->count();
        $onTimeRate      = $shiftsScheduled > 0
            ? round(($shiftsCompleted - $lateCount) / $shiftsScheduled, 2)
            : 1.0;

        $missedShifts  = max(0, $shiftsScheduled - $shiftsCompleted);
        $overtimeNote  = $overtimeHours > 0 ? "OT: {$overtimeHours}h this month." : 'No overtime.';
        $adjective     = $onTimeRate >= 0.95 ? 'Great month' : ($onTimeRate >= 0.8 ? 'Good month' : 'Tough month');
        $summary       = "{$adjective} — only {$missedShifts} missed shift(s). {$overtimeNote}";

        return response()->json([
            'month'            => $month,
            'hoursWorked'      => $hoursWorked,
            'shiftsCompleted'  => $shiftsCompleted,
            'shiftsScheduled'  => $shiftsScheduled,
            'onTimeRate'       => $onTimeRate,
            'overtimeHours'    => $overtimeHours,
            'summary'          => $summary,
        ]);
    }

    public function weekly(Request $request): JsonResponse
    {
        $week  = $request->query('week', now()->startOfWeek()->toDateString());
        $start = \Carbon\Carbon::parse($week)->startOfWeek();
        $end   = $start->copy()->endOfWeek();

        $user = $request->user();

        $days = [];
        $current = $start->copy();
        $dayLabels = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];

        for ($i = 0; $i < 7; $i++) {
            $date = $current->copy()->addDays($i);
            $hours = PunchSession::where('user_id', $user->id)
                ->where('state', 'clocked_out')
                ->whereDate('clocked_in_at', $date->toDateString())
                ->sum('work_seconds') / 3600;

            $days[] = ['day' => $dayLabels[$i], 'hours' => round($hours, 1)];
        }

        return response()->json(['week' => $week, 'days' => $days]);
    }
}

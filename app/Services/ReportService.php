<?php

namespace App\Services;

use App\Models\PunchSession;
use App\Models\SavedReport;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function generate(SavedReport $report): array
    {
        return match ($report->report_type) {
            'payroll'    => $this->payroll($report),
            'overtime'   => $this->overtime($report),
            'late'       => $this->late($report),
            'attendance' => $this->attendance($report),
            default      => [],
        };
    }

    private function payroll(SavedReport $report): array
    {
        return PunchSession::query()
            ->join('users', 'users.id', '=', 'punch_sessions.user_id')
            ->where('users.org_id', $report->org_id)
            ->where('punch_sessions.state', 'clocked_out')
            ->whereBetween('punch_sessions.clocked_in_at', [$report->date_range_start, $report->date_range_end])
            ->select(
                'users.full_name as Employee Name',
                'users.email as Email',
                DB::raw("'{$report->date_range_start}' as \"Period Start\""),
                DB::raw("'{$report->date_range_end}' as \"Period End\""),
                DB::raw('ROUND(SUM(punch_sessions.work_seconds) / 3600.0, 2) as "Work Hours"'),
                DB::raw('ROUND(SUM(punch_sessions.break_seconds) / 3600.0, 2) as "Break Hours"'),
                DB::raw('ROUND(SUM(punch_sessions.overtime_seconds) / 3600.0, 2) as "Overtime Hours"'),
                DB::raw('COUNT(*) as "Shift Count"')
            )
            ->groupBy('users.id', 'users.full_name', 'users.email')
            ->orderBy('users.full_name')
            ->get()
            ->toArray();
    }

    private function overtime(SavedReport $report): array
    {
        return PunchSession::query()
            ->join('users', 'users.id', '=', 'punch_sessions.user_id')
            ->where('users.org_id', $report->org_id)
            ->where('punch_sessions.state', 'clocked_out')
            ->where('punch_sessions.overtime_seconds', '>', 0)
            ->whereBetween('punch_sessions.clocked_in_at', [$report->date_range_start, $report->date_range_end])
            ->select(
                'users.full_name as Employee Name',
                'users.email as Email',
                DB::raw('ROUND(SUM(punch_sessions.overtime_seconds) / 3600.0, 2) as "Overtime Hours"'),
                DB::raw('COUNT(*) as "Sessions with OT"')
            )
            ->groupBy('users.id', 'users.full_name', 'users.email')
            ->orderByDesc('Overtime Hours')
            ->get()
            ->toArray();
    }

    private function late(SavedReport $report): array
    {
        return DB::table('punch_events')
            ->join('punch_sessions', 'punch_sessions.id', '=', 'punch_events.session_id')
            ->join('users', 'users.id', '=', 'punch_sessions.user_id')
            ->join('sites', 'sites.id', '=', 'punch_sessions.site_id')
            ->join('shifts', 'shifts.id', '=', 'punch_sessions.shift_id')
            ->where('users.org_id', $report->org_id)
            ->where('punch_events.event_type', 'clock_in')
            ->where('punch_events.flag_reason', 'like', 'late%')
            ->whereBetween('punch_events.occurred_at', [$report->date_range_start, $report->date_range_end])
            ->select(
                'users.full_name as Employee Name',
                DB::raw('DATE(punch_events.occurred_at) as Date'),
                'shifts.scheduled_start as Scheduled Start',
                'punch_events.occurred_at as Actual Clock-In',
                DB::raw("REPLACE(punch_events.flag_reason, 'late ', '') as \"Minutes Late\""),
                'sites.name as Site',
                'punch_events.method as Method',
                DB::raw('CASE WHEN punch_events.is_flagged THEN "Yes" ELSE "No" END as Flagged')
            )
            ->orderBy('punch_events.occurred_at')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    private function attendance(SavedReport $report): array
    {
        return Shift::query()
            ->join('users', 'users.id', '=', 'shifts.user_id')
            ->join('sites', 'sites.id', '=', 'shifts.site_id')
            ->leftJoin('punch_sessions', 'punch_sessions.shift_id', '=', 'shifts.id')
            ->where('shifts.org_id', $report->org_id)
            ->whereBetween('shifts.scheduled_start', [$report->date_range_start, $report->date_range_end])
            ->select(
                'users.full_name as Employee Name',
                DB::raw('DATE(shifts.scheduled_start) as "Shift Date"'),
                'shifts.scheduled_start as Scheduled Start',
                'shifts.scheduled_end as Scheduled End',
                'sites.name as Site',
                DB::raw("CASE
                    WHEN punch_sessions.id IS NULL THEN 'no-show'
                    WHEN punch_sessions.is_flagged = 1 THEN 'late'
                    ELSE 'present'
                END as Status")
            )
            ->orderBy('shifts.scheduled_start')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }
}

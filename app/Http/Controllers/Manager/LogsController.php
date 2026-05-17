<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\BreakRecord;
use App\Models\PunchEvent;
use App\Models\PunchSession;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogsController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $orgId = $request->org_id;
        $page  = (int) $request->query('page', 1);

        $query = PunchEvent::query()
            ->join('punch_sessions', 'punch_sessions.id', '=', 'punch_events.session_id')
            ->join('users', 'users.id', '=', 'punch_events.user_id')
            ->join('sites', 'sites.id', '=', 'punch_sessions.site_id')
            ->where('users.org_id', $orgId)
            ->select(
                'punch_events.id',
                'punch_events.occurred_at',
                'users.full_name as userName',
                'punch_events.event_type',
                'punch_events.method',
                'punch_events.is_flagged',
                'punch_events.flag_reason',
                'sites.name as siteName'
            );

        if ($from = $request->query('from')) {
            $query->where('punch_events.occurred_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->where('punch_events.occurred_at', '<=', $to);
        }
        if ($eventType = $request->query('eventType')) {
            $query->where('punch_events.event_type', $eventType);
        }
        if ($userId = $request->query('userId')) {
            $query->where('punch_events.user_id', $userId);
        }
        if ($siteId = $request->query('siteId')) {
            $query->where('punch_sessions.site_id', $siteId);
        }

        $paginated = $query->orderByDesc('punch_events.occurred_at')->paginate(50, ['*'], 'page', $page);

        $logs = collect($paginated->items())->map(fn($row) => [
            'id'          => $row->id,
            'occurredAt'  => $row->occurred_at,
            'userName'    => $row->userName,
            'eventType'   => $row->event_type,
            'description' => str_replace('_', '-', $row->event_type) . ' · ' . $row->siteName,
            'method'      => $row->method,
            'isFlagged'   => (bool) $row->is_flagged,
            'flagReason'  => $row->flag_reason,
        ]);

        return response()->json([
            'logs'       => $logs,
            'nextCursor' => $paginated->nextPageUrl(),
        ]);
    }

    public function manualAdj(Request $request): JsonResponse
    {
        $request->validate([
            'sessionId'         => 'required|uuid',
            'note'              => 'required|string',
            'adjustedClockIn'   => 'nullable|date',
            'adjustedClockOut'  => 'nullable|date',
        ]);

        $session = PunchSession::whereHas('user', fn($q) => $q->where('org_id', $request->org_id))
            ->where('id', $request->sessionId)
            ->with('breaks', 'shift')
            ->firstOrFail();

        DB::transaction(function () use ($session, $request) {
            $updates = ['notes' => $request->note, 'is_flagged' => true];

            if ($request->adjustedClockIn) {
                $updates['clocked_in_at'] = $request->adjustedClockIn;
            }
            if ($request->adjustedClockOut) {
                $updates['clocked_out_at'] = $request->adjustedClockOut;
            }

            $session->update($updates);

            // Recompute totals
            $clockIn  = $session->fresh()->clocked_in_at;
            $clockOut = $session->fresh()->clocked_out_at;

            if ($clockIn && $clockOut) {
                $totalBreak   = $session->breaks->sum('duration_seconds');
                $totalElapsed = $clockOut->diffInSeconds($clockIn);
                $workSeconds  = max(0, $totalElapsed - $totalBreak);
                $overtimeSec  = 0;

                if ($session->shift) {
                    $shiftDuration = $session->shift->scheduled_end->diffInSeconds($session->shift->scheduled_start);
                    $overtimeSec   = max(0, $totalElapsed - $shiftDuration);
                }

                $session->update([
                    'work_seconds'     => $workSeconds,
                    'break_seconds'    => $totalBreak,
                    'overtime_seconds' => $overtimeSec,
                ]);
            }

            PunchEvent::create([
                'session_id'  => $session->id,
                'user_id'     => $session->user_id,
                'event_type'  => 'manual_adj',
                'occurred_at' => now(),
                'actor_id'    => $request->user()->id,
                'is_flagged'  => true,
                'flag_reason' => 'manual adj · admin',
            ]);
        });

        // Notify employee
        $this->notifications->create(
            $session->user,
            'manual_adj',
            'Time adjusted',
            "Your clock-in for " . $session->clocked_in_at->format('M d') . " was adjusted by your manager"
        );

        return response()->json(['message' => 'Adjustment applied.']);
    }
}

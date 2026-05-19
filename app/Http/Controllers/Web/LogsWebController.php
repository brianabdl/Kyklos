<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PunchEvent;
use App\Models\PunchSession;
use App\Models\Site;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LogsWebController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request)
    {
        $orgId     = auth()->user()->org_id;
        $from      = $request->query('from');
        $to        = $request->query('to');
        $eventType = $request->query('event_type');
        $userId    = $request->query('user');
        $siteId    = $request->query('site');

        $query = PunchEvent::query()
            ->join('punch_sessions', 'punch_sessions.id', '=', 'punch_events.session_id')
            ->join('users', 'users.id', '=', 'punch_events.user_id')
            ->join('sites', 'sites.id', '=', 'punch_sessions.site_id')
            ->where('users.org_id', $orgId)
            ->select(
                'punch_events.id',
                'punch_events.occurred_at',
                'users.full_name as user_name',
                'punch_events.event_type',
                'punch_events.method',
                'punch_events.is_flagged',
                'punch_events.flag_reason',
                'sites.name as site_name'
            );

        if ($from) {
            $query->where('punch_events.occurred_at', '>=', $from);
        }
        if ($to) {
            $query->where('punch_events.occurred_at', '<=', $to . ' 23:59:59');
        }
        if ($eventType) {
            $query->where('punch_events.event_type', $eventType);
        }
        if ($userId) {
            $query->where('punch_events.user_id', $userId);
        }
        if ($siteId) {
            $query->where('punch_sessions.site_id', $siteId);
        }

        $logs      = $query->orderByDesc('punch_events.occurred_at')->paginate(50)->withQueryString();
        $employees = User::where('org_id', $orgId)->where('role', 'employee')->orderBy('full_name')->get();
        $sites     = Site::where('org_id', $orgId)->orderBy('name')->get();

        $eventTypes = ['clock_in', 'clock_out', 'break_start', 'break_end', 'manual_adj'];

        // Recent sessions for the manual adj / void section
        $sessionQuery = PunchSession::query()
            ->join('users', 'users.id', '=', 'punch_sessions.user_id')
            ->join('sites', 'sites.id', '=', 'punch_sessions.site_id')
            ->where('users.org_id', $orgId)
            ->select(
                'punch_sessions.id',
                'punch_sessions.state',
                'punch_sessions.clocked_in_at',
                'punch_sessions.clocked_out_at',
                'punch_sessions.is_flagged',
                'users.full_name as user_name',
                'users.id as user_id',
                'sites.name as site_name'
            );

        if ($userId) {
            $sessionQuery->where('punch_sessions.user_id', $userId);
        }
        if ($from) {
            $sessionQuery->where('punch_sessions.clocked_in_at', '>=', $from);
        }
        if ($to) {
            $sessionQuery->where('punch_sessions.clocked_in_at', '<=', $to . ' 23:59:59');
        }

        $sessions = $sessionQuery->orderByDesc('punch_sessions.clocked_in_at')->limit(50)->get();

        return view('dashboard.logs', compact(
            'logs', 'employees', 'sites', 'eventTypes', 'sessions',
            'from', 'to', 'eventType', 'userId', 'siteId'
        ));
    }

    public function manualAdj(Request $request)
    {
        $request->validate([
            'session_id'        => 'required|uuid',
            'note'              => 'required|string|max:500',
            'adjusted_clock_in' => 'nullable|date',
            'adjusted_clock_out'=> 'nullable|date',
        ]);

        $orgId   = Auth::user()->org_id;
        $session = PunchSession::whereHas('user', fn($q) => $q->where('org_id', $orgId))
            ->where('id', $request->session_id)
            ->with('breaks', 'shift')
            ->firstOrFail();

        DB::transaction(function () use ($session, $request) {
            $updates = ['notes' => $request->note, 'is_flagged' => true];

            if ($request->adjusted_clock_in) {
                $updates['clocked_in_at'] = $request->adjusted_clock_in;
            }
            if ($request->adjusted_clock_out) {
                $updates['clocked_out_at'] = $request->adjusted_clock_out;
            }

            $session->update($updates);

            $fresh     = $session->fresh();
            $clockIn   = $fresh->clocked_in_at;
            $clockOut  = $fresh->clocked_out_at;

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
                'actor_id'    => Auth::id(),
                'is_flagged'  => true,
                'flag_reason' => 'manual adj · manager dashboard',
            ]);
        });

        $this->notifications->create(
            $session->user,
            'manual_adj',
            'Time adjusted',
            'Your punch session on ' . $session->clocked_in_at->format('M d') . ' was adjusted by your manager.'
        );

        return redirect()->route('dashboard.logs')->with('success', 'Adjustment applied.');
    }

    public function voidSession(Request $request, string $id)
    {
        $orgId   = Auth::user()->org_id;
        $session = PunchSession::whereHas('user', fn($q) => $q->where('org_id', $orgId))
            ->where('id', $id)
            ->firstOrFail();

        DB::transaction(function () use ($session) {
            $session->update(['state' => 'voided']);

            PunchEvent::create([
                'session_id'  => $session->id,
                'user_id'     => $session->user_id,
                'event_type'  => 'manual_adj',
                'occurred_at' => now(),
                'actor_id'    => Auth::id(),
                'is_flagged'  => true,
                'flag_reason' => 'session voided · manager dashboard',
            ]);
        });

        $this->notifications->create(
            $session->user,
            'manual_adj',
            'Session voided',
            'Your punch session on ' . $session->clocked_in_at->format('M d') . ' was voided by your manager.'
        );

        return redirect()->route('dashboard.logs')->with('success', 'Session voided.');
    }
}

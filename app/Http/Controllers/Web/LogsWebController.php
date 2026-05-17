<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PunchEvent;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;

class LogsWebController extends Controller
{
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

        return view('dashboard.logs', compact(
            'logs', 'employees', 'sites', 'eventTypes',
            'from', 'to', 'eventType', 'userId', 'siteId'
        ));
    }
}

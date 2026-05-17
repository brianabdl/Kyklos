<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;

class TeamWebController extends Controller
{
    public function index(Request $request)
    {
        $orgId  = auth()->user()->org_id;
        $date   = $request->query('date', today()->toDateString());
        $search = $request->query('search');
        $status = $request->query('status');
        $siteId = $request->query('site');

        $query = User::where('org_id', $orgId)
            ->where('role', 'employee')
            ->where('is_active', true)
            ->with([
                'punchSessions' => fn($q) => $q
                    ->whereDate('clocked_in_at', $date)
                    ->with('site')
                    ->latest('clocked_in_at')
                    ->limit(1),
            ]);

        if ($search) {
            $query->where('full_name', 'like', "%{$search}%");
        }

        $employees = $query->get()->map(function (User $user) {
            $session     = $user->punchSessions->first();
            $statusLabel = 'out';
            $hoursToday  = null;
            $clockedInAt = null;

            if ($session) {
                $statusLabel = match ($session->state) {
                    'active'   => 'in',
                    'on_break' => 'break',
                    default    => 'out',
                };
                $clockedInAt = $session->clocked_in_at;
                $elapsed     = now()->diffInSeconds($session->clocked_in_at);
                $hoursToday  = floor($elapsed / 3600) . 'h ' . floor(($elapsed % 3600) / 60) . 'm';
            }

            return (object) [
                'id'         => $user->id,
                'name'       => $user->full_name,
                'site'       => $session?->site?->name,
                'status'     => $statusLabel,
                'clockedInAt'=> $clockedInAt,
                'hoursToday' => $hoursToday,
            ];
        });

        if ($status) {
            $employees = $employees->filter(fn($e) => $e->status === $status)->values();
        }

        $sites = Site::where('org_id', $orgId)->orderBy('name')->get();

        return view('dashboard.team', compact('employees', 'date', 'search', 'status', 'sites', 'siteId'));
    }
}

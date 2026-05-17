<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\PunchSession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orgId  = $request->org_id;
        $date   = $request->query('date', today()->toDateString());
        $status = $request->query('status');
        $siteId = $request->query('siteId');
        $search = $request->query('search');

        $query = User::where('org_id', $orgId)
            ->where('role', 'employee')
            ->where('is_active', true)
            ->with([
                'punchSessions' => fn($q) => $q
                    ->whereDate('clocked_in_at', $date)
                    ->with('site')
                    ->orderByDesc('clocked_in_at')
                    ->limit(1),
            ]);

        if ($search) {
            $query->where('full_name', 'like', "%{$search}%");
        }

        $employees = $query->get()->map(function (User $user) use ($date) {
            $session = $user->punchSessions->first();
            $statusLabel = 'out';
            $hoursToday  = null;
            $clockedInAt = null;
            $sessionId   = null;

            if ($session) {
                $statusLabel = match ($session->state) {
                    'active'    => 'in',
                    'on_break'  => 'break',
                    default     => 'out',
                };
                $clockedInAt = $session->clocked_in_at?->toISOString();
                $sessionId   = $session->id;
                $elapsed     = now()->diffInSeconds($session->clocked_in_at);
                $hours       = floor($elapsed / 3600);
                $minutes     = floor(($elapsed % 3600) / 60);
                $hoursToday  = "{$hours}h {$minutes}m";
            }

            return [
                'userId'      => $user->id,
                'name'        => $user->full_name,
                'site'        => $session?->site?->name,
                'status'      => $statusLabel,
                'clockedInAt' => $clockedInAt,
                'hoursToday'  => $hoursToday,
                'sessionId'   => $sessionId,
            ];
        });

        if ($status) {
            $employees = $employees->filter(fn($e) => $e['status'] === $status)->values();
        }

        if ($siteId) {
            $employees = $employees->filter(fn($e) => $e['siteId'] ?? null === $siteId)->values();
        }

        return response()->json(['employees' => $employees]);
    }
}

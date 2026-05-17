<?php

use App\Models\PunchSession;
use App\Models\Shift;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Schedule;

// No-show check: every 5 minutes
Schedule::call(function () {
    $notif = app(NotificationService::class);

    // Find shifts that started 30+ min ago with no punch session
    $noShows = Shift::query()
        ->whereDoesntHave('punchSessions')
        ->where('scheduled_start', '<', now()->subMinutes(30))
        ->where('scheduled_start', '>', now()->subHours(4)) // only within 4h window
        ->with('user', 'site')
        ->get();

    foreach ($noShows as $shift) {
        // Check if we already sent a no-show notification today
        $alreadySent = $shift->user->notifications()
            ->where('type', 'no_show')
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadySent) {
            continue;
        }

        // Find the manager for this org
        $manager = User::where('org_id', $shift->user->org_id)
            ->where('role', 'manager')
            ->first();

        if ($manager) {
            $notif->create(
                $manager,
                'no_show',
                'No-show alert',
                "{$shift->user->full_name} hasn't clocked in for {$shift->site->name} morning shift",
                ['userId' => $shift->user_id, 'shiftId' => $shift->id, 'deepLink' => 'kyklos://manager/team']
            );
        }
    }
})->everyFiveMinutes();

// Shift reminder: 1 hour before shift start
Schedule::call(function () {
    $notif = app(NotificationService::class);

    $upcoming = Shift::query()
        ->whereBetween('scheduled_start', [now()->addMinutes(55), now()->addMinutes(65)])
        ->with('user', 'site')
        ->get();

    foreach ($upcoming as $shift) {
        $alreadySent = $shift->user->notifications()
            ->where('type', 'shift_reminder')
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadySent) {
            continue;
        }

        $notif->create(
            $shift->user,
            'shift_reminder',
            'Shift reminder',
            "Your {$shift->label} at {$shift->site->name} starts at " . $shift->scheduled_start->format('H:i') . ' (in 1 hr)',
            ['shiftId' => $shift->id, 'deepLink' => 'kyklos://employee/home']
        );
    }
})->hourly();

// Overtime check: every 15 minutes
Schedule::call(function () {
    $notif = app(NotificationService::class);

    $activeSessions = PunchSession::query()
        ->whereIn('state', ['active', 'on_break'])
        ->whereNotNull('shift_id')
        ->with('user', 'shift')
        ->get();

    foreach ($activeSessions as $session) {
        if (!$session->shift) {
            continue;
        }

        $elapsed = now()->diffInSeconds($session->clocked_in_at);
        $shiftDuration = $session->shift->scheduled_end->diffInSeconds($session->shift->scheduled_start);

        if ($elapsed <= $shiftDuration) {
            continue;
        }

        $overtimeHours = round(($elapsed - $shiftDuration) / 3600, 1);

        $alreadyNotified = $session->user->notifications()
            ->where('type', 'ot_request')
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadyNotified) {
            continue;
        }

        $manager = User::where('org_id', $session->user->org_id)
            ->where('role', 'manager')
            ->first();

        if ($manager) {
            $notif->create(
                $manager,
                'ot_request',
                'OT detected',
                "{$session->user->full_name} is {$overtimeHours}h into their shift. Approve?",
                ['sessionId' => $session->id, 'deepLink' => 'kyklos://manager/logs']
            );
        }
    }
})->everyFifteenMinutes();

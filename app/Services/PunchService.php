<?php

namespace App\Services;

use App\Exceptions\PunchException;
use App\Models\BreakRecord;
use App\Models\PunchEvent;
use App\Models\PunchSession;
use App\Models\Shift;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PunchService
{
    public function __construct(
        private GeofenceService $geofence,
        private NotificationService $notifications,
    ) {}

    /**
     * @return array{session: PunchSession, geofenceCheck: array}
     * @throws PunchException
     */
    public function clockIn(User $user, array $data): array
    {
        $siteId   = $data['siteId'];
        $method   = $data['method'];
        $lat      = $data['lat'] ?? null;
        $lng      = $data['lng'] ?? null;
        $photoUrl = $data['photoUrl'] ?? null;

        $existing = PunchSession::where('user_id', $user->id)
            ->whereIn('state', ['active', 'on_break'])
            ->first();

        if ($existing) {
            throw new PunchException('ALREADY_CLOCKED_IN', 'You already have an active session.', 409);
        }

        $site = Site::where('id', $siteId)->where('org_id', $user->org_id)->first();
        if (!$site) {
            throw new PunchException('SITE_NOT_FOUND', 'Site not found.', 404);
        }

        $geofenceResult = ['inside' => true, 'distanceMeters' => 0, 'flagged' => false, 'flagReason' => null];
        if ($method === 'gps' && $lat !== null) {
            $geofenceResult = $this->geofence->check($site, $lat, $lng);
            if (!$geofenceResult['inside']) {
                throw new PunchException(
                    'OUTSIDE_GEOFENCE',
                    "You are {$geofenceResult['distanceMeters']} m from {$site->name} (max {$site->geofence_radius_m} m)",
                    422
                );
            }
        }

        $shift = Shift::where('user_id', $user->id)
            ->whereDate('scheduled_start', today())
            ->orderBy('scheduled_start')
            ->first();

        $isFlagged  = $geofenceResult['flagged'];
        $flagReason = $geofenceResult['flagReason'];

        if ($shift && now()->gt($shift->scheduled_start->addMinutes(5))) {
            $diffMinutes = (int) now()->diffInMinutes($shift->scheduled_start);
            $isFlagged   = true;
            $flagReason  = ($flagReason ? $flagReason . ', ' : '') . "late {$diffMinutes}m";
        }

        $session = DB::transaction(function () use ($user, $site, $shift, $method, $lat, $lng, $photoUrl, $isFlagged, $flagReason) {
            $session = PunchSession::create([
                'user_id'         => $user->id,
                'site_id'         => $site->id,
                'shift_id'        => $shift?->id,
                'state'           => 'active',
                'clocked_in_at'   => now(),
                'clock_in_method' => $method,
                'clock_in_lat'    => $lat,
                'clock_in_lng'    => $lng,
                'is_flagged'      => $isFlagged,
            ]);

            PunchEvent::create([
                'session_id'  => $session->id,
                'user_id'     => $user->id,
                'event_type'  => 'clock_in',
                'occurred_at' => now(),
                'method'      => $method,
                'lat'         => $lat,
                'lng'         => $lng,
                'photo_url'   => $photoUrl,
                'is_flagged'  => $isFlagged,
                'flag_reason' => $flagReason,
            ]);

            return $session;
        });

        $this->notifications->create(
            $user,
            'punch.clock_in',
            'Clocked In',
            "You clocked in at {$site->name} · " . now()->format('H:i'),
            ['sessionId' => $session->id, 'siteId' => $site->id],
        );

        return [
            'session'       => $session->load('site', 'shift'),
            'geofenceCheck' => [
                'insideGeofence' => $geofenceResult['inside'],
                'distanceMeters' => round($geofenceResult['distanceMeters']),
            ],
        ];
    }

    /**
     * @throws PunchException
     */
    public function clockOut(User $user, array $data): PunchSession
    {
        $method = $data['method'];
        $lat    = $data['lat'] ?? null;
        $lng    = $data['lng'] ?? null;

        $session = PunchSession::where('user_id', $user->id)
            ->whereIn('state', ['active', 'on_break'])
            ->with('shift', 'breaks')
            ->first();

        if (!$session) {
            throw new PunchException('NO_ACTIVE_SESSION', 'No active session.', 404);
        }

        DB::transaction(function () use ($session, $method, $lat, $lng, $user) {
            $openBreak = $session->breaks->whereNull('ended_at')->first();
            if ($openBreak) {
                $openBreak->update([
                    'ended_at'         => now(),
                    'duration_seconds' => now()->diffInSeconds($openBreak->started_at),
                ]);
                $session->refresh();
            }

            $totalBreak   = $session->breaks->sum('duration_seconds');
            $totalElapsed = now()->diffInSeconds($session->clocked_in_at);
            $workSeconds  = max(0, $totalElapsed - $totalBreak);
            $overtimeSec  = 0;

            if ($session->shift) {
                $shiftDuration = $session->shift->scheduled_end->diffInSeconds($session->shift->scheduled_start);
                $overtimeSec   = max(0, $totalElapsed - $shiftDuration);
            }

            $session->update([
                'state'            => 'clocked_out',
                'clocked_out_at'   => now(),
                'clock_out_method' => $method,
                'work_seconds'     => $workSeconds,
                'break_seconds'    => $totalBreak,
                'overtime_seconds' => $overtimeSec,
            ]);

            PunchEvent::create([
                'session_id'  => $session->id,
                'user_id'     => $user->id,
                'event_type'  => 'clock_out',
                'occurred_at' => now(),
                'method'      => $method,
                'lat'         => $lat,
                'lng'         => $lng,
            ]);
        });

        $fresh = $session->fresh()->load('site', 'shift');
        $h = intdiv($fresh->work_seconds, 3600);
        $m = intdiv($fresh->work_seconds % 3600, 60);

        $this->notifications->create(
            $user,
            'punch.clock_out',
            'Clocked Out',
            "Good work! You clocked out after {$h}h {$m}m · " . now()->format('H:i'),
            ['sessionId' => $fresh->id],
        );

        return $fresh;
    }

    /**
     * @throws PunchException
     */
    public function breakStart(User $user): PunchSession
    {
        $session = PunchSession::where('user_id', $user->id)->where('state', 'active')->first();

        if (!$session) {
            throw new PunchException('NO_ACTIVE_SESSION', 'No active session to start break on.', 409);
        }

        DB::transaction(function () use ($session, $user) {
            BreakRecord::create(['session_id' => $session->id, 'started_at' => now()]);
            $session->update(['state' => 'on_break']);
            PunchEvent::create([
                'session_id'  => $session->id,
                'user_id'     => $user->id,
                'event_type'  => 'break_start',
                'occurred_at' => now(),
            ]);
        });

        $this->notifications->create(
            $user,
            'punch.break_start',
            'Break Started',
            'Your break has started · ' . now()->format('H:i'),
            ['sessionId' => $session->id],
        );

        return $session->fresh()->load('site', 'shift', 'breaks');
    }

    /**
     * @throws PunchException
     */
    public function breakEnd(User $user): PunchSession
    {
        $session = PunchSession::where('user_id', $user->id)->where('state', 'on_break')->first();

        if (!$session) {
            throw new PunchException('NO_ON_BREAK_SESSION', 'No on-break session found.', 409);
        }

        DB::transaction(function () use ($session, $user) {
            $openBreak = BreakRecord::where('session_id', $session->id)->whereNull('ended_at')->first();
            if ($openBreak) {
                $openBreak->update([
                    'ended_at'         => now(),
                    'duration_seconds' => now()->diffInSeconds($openBreak->started_at),
                ]);
            }
            $session->update(['state' => 'active']);
            PunchEvent::create([
                'session_id'  => $session->id,
                'user_id'     => $user->id,
                'event_type'  => 'break_end',
                'occurred_at' => now(),
            ]);
        });

        $this->notifications->create(
            $user,
            'punch.break_end',
            'Break Ended',
            'Welcome back! Break ended · ' . now()->format('H:i'),
            ['sessionId' => $session->id],
        );

        return $session->fresh()->load('site', 'shift', 'breaks');
    }

    public function activeSession(User $user): ?PunchSession
    {
        return PunchSession::where('user_id', $user->id)
            ->whereIn('state', ['active', 'on_break'])
            ->with('site', 'shift', 'breaks')
            ->first();
    }
}

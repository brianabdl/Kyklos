<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    public function create(
        User $user,
        string $type,
        string $title,
        string $body,
        ?array $payload = null
    ): AppNotification {
        $notification = AppNotification::create([
            'user_id' => $user->id,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'payload' => $payload,
            'is_read' => false,
        ]);

        broadcast(new NotificationCreated($notification));

        return $notification;
    }
}

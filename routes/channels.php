<?php

use Illuminate\Support\Facades\Broadcast;

// Override default middleware so API token clients can authenticate channels
Broadcast::routes(['middleware' => ['auth:sanctum']]);

Broadcast::channel('notifications.{userId}', function ($user, string $userId) {
    return $user->id === $userId;
});

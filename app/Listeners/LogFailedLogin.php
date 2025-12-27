<?php

namespace App\Listeners;

use App\Services\AppLogger;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        AppLogger::warning(
            category: 'auth',
            event: 'FailedLogin',
            message: 'Failed login attempt',
            context: [
                'user_id' => $event->user->id ?? null,
                'email' => $event->credentials['email'] ?? 'unknown',
            ],
            subject: $event->user
        );
    }
}

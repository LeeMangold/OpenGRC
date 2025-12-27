<?php

namespace App\Listeners;

use App\Services\AppLogger;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function handle(Logout $event): void
    {
        // Skip logging if no user was logged in (e.g., during Dusk tests)
        if (! $event->user) {
            return;
        }

        AppLogger::info(
            category: 'auth',
            event: 'Logout',
            message: 'User logged out',
            context: [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
            ],
            subject: $event->user,
            causer: $event->user
        );
    }
}

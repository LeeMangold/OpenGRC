<?php

namespace App\Listeners;

use App\Services\AppLogger;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        AppLogger::info(
            category: 'auth',
            event: 'Login',
            message: 'User logged in',
            context: [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
            ],
            subject: $event->user,
            causer: $event->user
        );
    }
}

<?php

namespace App\Livewire;

use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

class MultiWindowInactivityGuard extends Component
{
    protected const MILLISECONDS_PER_SECOND = 1000;

    public function render(): string|View
    {
        if (Filament::auth()->guest()) {
            return '<div></div>';
        }

        $inactivityTimeout = 15; // Default timeout in minutes
        try {
            $inactivityTimeout = setting('security.session_timeout', 15);
        } catch (\Exception $e) {
            // Use default if settings not available
        }

        $noticeTimeoutSeconds = 60; // 60 second warning before logout
        $totalTimeoutSeconds = $inactivityTimeout * Carbon::SECONDS_PER_MINUTE;
        $inactivityTimeoutSeconds = $totalTimeoutSeconds - $noticeTimeoutSeconds;

        return view('livewire.multi-window-inactivity-guard', [
            'inactivity_timeout' => $inactivityTimeoutSeconds * static::MILLISECONDS_PER_SECOND,
            'notice_timeout' => $noticeTimeoutSeconds * static::MILLISECONDS_PER_SECOND,
            'interaction_events' => json_encode(['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click']),
        ]);
    }

    public function logout(): string
    {
        Filament::auth()->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        Notification::make()
            ->warning()
            ->title(__('Session expired due to inactivity'))
            ->persistent()
            ->send();

        // Return the login URL so JavaScript can do a full page redirect
        // This prevents the login page from appearing in a Livewire modal
        return Filament::getLoginUrl();
    }
}

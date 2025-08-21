<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SessionTimeoutWarning extends Component
{
    public $showWarning = false;
    public $timeRemaining = 60;

    protected $listeners = ['showTimeoutWarning' => 'showWarning', 'checkSessionStatus' => 'checkSession'];

    public function showWarning()
    {
        $this->showWarning = true;
        $this->timeRemaining = 60;
    }

    public function extendSession()
    {
        if (Auth::check()) {
            // Update user's last_activity
            DB::table('users')
                ->where('id', Auth::id())
                ->update(['last_activity' => now()]);
            
            $this->showWarning = false;
            $this->dispatch('sessionExtended');
        }
    }

    public function logout()
    {
        // Hide the modal first
        $this->showWarning = false;
        
        // Log out using standard Laravel Auth
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        
        // Use Livewire redirect method
        $this->redirect(route('filament.app.auth.login'), navigate: false);
    }

    public function checkSession()
    {
        if (!Auth::check()) {
            $this->redirect(route('filament.app.auth.login'), navigate: false);
            return;
        }

        $timeout = setting('security.session_timeout', 15) * 60;
        $user = Auth::user();
        
        $currentActivity = DB::table('users')
            ->where('id', $user->id)
            ->value('last_activity');

        if ($currentActivity) {
            $timeLeft = ($timeout - (now()->timestamp - strtotime($currentActivity)));
            
            if ($timeLeft <= 0) {
                $this->logout();
            } elseif ($timeLeft <= 60 && !$this->showWarning) {
                $this->showWarning = true;
                $this->timeRemaining = 60;
            }
        }
    }

    public function render()
    {
        return view('livewire.session-timeout-warning');
    }
}

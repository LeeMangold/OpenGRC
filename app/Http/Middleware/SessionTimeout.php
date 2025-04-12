<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SessionTimeout
{
    public function handle(Request $request, Closure $next)
    {
        Log::debug('SessionTimeout middleware');
        if (!Auth::check()) {
            return $next($request);
        }

        $timeout = 15 * 60; // Default timeout in seconds
        $user = auth()->user();

        // Get current last_activity directly from database
        $currentActivity = DB::table('users')
            ->where('id', $user->id)
            ->value('last_activity');

        // If session_timeout is set, use it.
        if (setting('security.session_timeout')) {
            $timeout = setting('security.session_timeout') * 60;
        }

        // If the user has been inactive for longer than the timeout, log them out.
        if ($currentActivity && strtotime($currentActivity) + $timeout < now()->timestamp) {
            // Prepare the redirect response before clearing the session
            $redirect = redirect()->route('filament.app.auth.login')->with('error', 'Your session has expired due to inactivity.');
            
            // Now perform the logout operations
            Auth::logout();
            Session::flush();
            
            return $redirect;
        }

        return $next($request);
    }
}

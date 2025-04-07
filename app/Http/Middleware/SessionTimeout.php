<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SessionTimeout
{
    public function handle(Request $request, Closure $next)
    {

        $timeout = 15 * 60; // Default timeout in seconds
        Session::has('last_activity') || Session::put('last_activity', now()->timestamp);

        // Ignore non-interactive Livewire requests
        if (! str_contains($request->path(), 'livewire/update')) {
            Session::put('last_activity', now()->timestamp);
        }

        // If session_timeout is set, use it.
        if (setting('security.session_timeout')) {
            $timeout = setting('security.session_timeout') * 60;
        }

        // If the user has been inactive for longer than the timeout, log them out.
        if (now()->timestamp - Session::get('last_activity', 0) > $timeout) {
            Auth::logout();
            Session::flush();

            return redirect()->route('filament.app.auth.login')->with('error', 'Your session has expired due to inactivity.');
        }

        return $next($request);
    }
}

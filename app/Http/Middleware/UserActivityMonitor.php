<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class UserActivityMonitor
{
    private function isLivewireRequest(Request $request): bool 
    {
        return str_contains(strtolower($request->path()), 'livewire/update');
    }

    private function isLoginRoute(Request $request): bool
    {
        return $request->is('*login*') || $request->is('*/auth/login');
    }

    private function updateLastActivity($userId)
    {
        DB::table('users')
            ->where('id', $userId)
            ->update(['last_activity' => now()]);
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        // Get post-response auth state
        $isLoggedIn = Auth::check();
        if ($isLoggedIn && !$this->isLivewireRequest($request)) {
            //$this->updateLastActivity($userId);
            Auth::user()->updateLastActivity();
        }

        return $next($request);
    }
}

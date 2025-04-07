<?php

namespace App\Http\Middleware;

use App\Models\User;
use Auth;
use Closure;
use Illuminate\Http\Request;
use Str;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordReset
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {

        // This is necessary for Livewire. There may be a better way to do this...
        if (Str::contains($request->session()->previousUrl(), 'app/reset-password')) {
            return $next($request);
        }

        // If the user is logged in and password reset is required, redirect to the password reset page
        /** @var User $user */
        $user = Auth::user();
        if (Auth::check() && $user->password_reset_required) {
            return redirect()->route('password-reset-page');
        }

        return $next($request);
    }
}

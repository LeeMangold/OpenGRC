<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function redirectToProvider($provider)
    {
        return \Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {
        $socialiteUser = \Socialite::driver($provider)->user();
        // Find or create user
        $user = \App\Models\User::firstOrCreate(
            ['email' => $socialiteUser->getEmail()],
            [
                'name' => $socialiteUser->getName(),
                'password' => bcrypt(Str::random(16)),
                'email_verified_at' => now(),
            ]
        );

        // Log the user in
        \Auth::login($user);

        // Redirect to the dashboard
        return redirect()->to('/app');
    }
}

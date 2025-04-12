<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {

        $socialiteUser = Socialite::driver($provider)->user();

        // Check if auto-provisioning is enabled for the provider
        $autoProvision = setting("auth.{$provider}.auto_provision", false);

        // Find existing user
        $user = \App\Models\User::where('email', $socialiteUser->getEmail())->first();

        if (! $user && ! $autoProvision) {
            // User doesn't exist and auto-provisioning is disabled
            abort(401, 'No account exists for this email address and auto-provisioning is disabled.');
        }

        if (! $user && $autoProvision) {
            // Create new user since auto-provisioning is enabled
            $user = \App\Models\User::create([
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail(),
                'password' => bcrypt(\Str::random(16)),
                'email_verified_at' => now(),
                'password_reset_required' => false,
            ]);

            // Assign the configured role if specified
            $roleId = setting("auth.{$provider}.role");
            if ($roleId) {
                $role = \Spatie\Permission\Models\Role::find($roleId);
                if ($role) {
                    $user->assignRole($role);
                }
            }
        }

        // Log the user in
        \Auth::login($user);
        $user->updateLastActivity();

        // Redirect to the dashboard
        return redirect()->to('/app');
    }
}

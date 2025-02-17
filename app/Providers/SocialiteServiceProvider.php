<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Crypt;

class SocialiteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! app()->runningInConsole() && Schema::hasTable('settings')) {
            $baseUrl = config('app.url');
            
            // Configure Okta
            if (setting('auth.okta.enabled')) {
                $clientSecret = setting('auth.okta.client_secret');
                config([
                    'services.okta' => [
                        'client_id' => setting('auth.okta.client_id'),
                        'client_secret' => $clientSecret ? Crypt::decryptString($clientSecret) : null,
                        'base_url' => setting('auth.okta.base_url'),
                        'redirect' => "{$baseUrl}/auth/okta/callback",
                    ],
                ]);
            }

            // Configure Microsoft
            if (setting('auth.microsoft.enabled')) {
                $clientSecret = setting('auth.microsoft.client_secret');
                config([
                    'services.microsoft' => [
                        'client_id' => setting('auth.microsoft.client_id'),
                        'client_secret' => $clientSecret ? Crypt::decryptString($clientSecret) : null,
                        'tenant' => setting('auth.microsoft.tenant', 'common'),
                        'redirect' => "{$baseUrl}/auth/microsoft/callback",
                    ],
                ]);
            }

            // Configure Azure
            if (setting('auth.azure.enabled')) {
                $clientSecret = setting('auth.azure.client_secret');
                config([
                    'services.azure' => [
                        'client_id' => setting('auth.azure.client_id'),
                        'client_secret' => $clientSecret ? Crypt::decryptString($clientSecret) : null,
                        'tenant' => setting('auth.azure.tenant', 'common'),
                        'redirect' => "{$baseUrl}/auth/azure/callback",
                    ],
                ]);
            }
        }
    }
} 
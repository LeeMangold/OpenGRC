<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OAuth callback middleware
    |--------------------------------------------------------------------------
    |
    | This option defines the middleware that is applied to the OAuth callback url.
    |
    */

    'middleware' => [
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    ],

    // 'login_route' => 'socialite.redirect',
    'login_route' => 'socialite.app.oauth.redirect',
    'callback_route' => 'socialite.callback',
    'user_model' => \App\Models\User::class,

    'providers' => [
        'okta' => [
            'label' => 'Okta',
            'icon' => 'heroicon-o-lock-closed',
            'color' => 'primary',
        ],
        'azure' => [
            'label' => 'Azure AD',
            'icon' => 'heroicon-o-cloud',
            'color' => 'primary',
        ],
    ],
];

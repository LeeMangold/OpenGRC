<?php

namespace App\Filament\Pages\Auth;

use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $result = parent::authenticate();
        
        // Update last activity after successful login
        auth()->user()?->updateLastActivity();
        
        return $result;
    }
} 
<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class VendorUser extends Authenticatable implements FilamentUser
{
    use HasFactory, LogsActivity, Notifiable, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'name',
        'email',
        'password',
        'email_verified_at',
        'last_login_at',
        'is_primary',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_primary' => 'boolean',
        'password' => 'hashed',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'vendor';
    }

    /**
     * Check if the user has set a password (account is activated).
     */
    public function hasPassword(): bool
    {
        return ! is_null($this->password);
    }

    /**
     * Check if this is a pending (not yet activated) account.
     */
    public function isPending(): bool
    {
        return ! $this->hasPassword();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_primary', 'vendor_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Send the password reset notification using the vendor panel's route.
     */
    public function sendPasswordResetNotification($token): void
    {
        // Set up the reset URL to use the vendor panel's password reset route with signature
        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            return URL::temporarySignedRoute(
                'filament.vendor.auth.password-reset.reset',
                now()->addMinutes(60),
                [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ]
            );
        });

        $this->notify(new ResetPassword($token));
    }
}

<?php

namespace App\Models;

use App\Enums\ResponseStatus;
use App\Traits\Concerns\HasSuperAdmin;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasRoles, HasSuperAdmin, Notifiable, softDeletes, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be guarded from mass assignment.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'last_activity'
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_activity' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function booted()
    {
        // static::saving(function ($user) {
        //     if ($user->isDirty('last_activity')) {
        //         Log::debug('Attempt to update last_activity through model save', [
        //             'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
        //             'dirty' => $user->getDirty()
        //         ]);
        //         // Prevent the update of last_activity through normal model operations
        //         $user->last_activity = $user->getOriginal('last_activity');
        //     }
        // });

        // static::updating(function ($user) {
        //     if ($user->isDirty('last_activity')) {
        //         Log::debug('Attempt to update last_activity through model update', [
        //             'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
        //             'dirty' => $user->getDirty()
        //         ]);
        //         // Prevent the update of last_activity through normal model operations
        //         $user->last_activity = $user->getOriginal('last_activity');
        //     }
        // });
    }

    /**
     * Update the user's last activity timestamp.
     *
     * @return bool
     */
    public function updateLastActivity(): void
    {       
        DB::table('users')
            ->where('id', $this->id)
            ->update(['last_activity' => now()]);

        Log::debug('User Logged In');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function audits(): BelongsToMany
    {
        return $this->belongsToMany(Audit::class);
    }

    public function todos(): HasMany
    {
        return $this->hasMany(DataRequestResponse::class, 'requestee_id');
    }

    public function openTodos(): HasMany
    {
        return $this->hasMany(DataRequestResponse::class, 'requestee_id')
            ->where('status', ResponseStatus::PENDING)
            ->orWhere('status', ResponseStatus::REJECTED);
    }

    public function managedPrograms(): HasMany
    {
        return $this->hasMany(Program::class, 'program_manager_id');
    }
}

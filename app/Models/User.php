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
use Illuminate\Support\Facades\DB;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Kirschbaum\Commentions\Contracts\Commenter;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Commenter, FilamentUser
{
    use HasApiTokens, HasFactory, HasRoles, HasSuperAdmin, LogsActivity, Notifiable, softDeletes, TwoFactorAuthenticatable;

    protected static $logOnlyDirty = true;

    protected static $logName = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'text',
        'email',
        'password',
    ];

    /**
     * The attributes that should be guarded from mass assignment.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'last_activity',
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

    /**
     * Update the user's last activity timestamp.
     */
    public function updateLastActivity(): void
    {
        DB::table('users')
            ->where('id', $this->id)
            ->update(['last_activity' => now()]);

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
            ->whereIn('status', [ResponseStatus::PENDING, ResponseStatus::REJECTED]);
    }

    public function managedPrograms(): HasMany
    {
        return $this->hasMany(Program::class, 'program_manager_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlids, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone_e164', 'password', 'status',
        'is_super_admin', 'email_verified_at', 'phone_verified_at', 'last_agency_id',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'is_super_admin' => 'boolean',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
        ];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function agencies(): BelongsToMany
    {
        return $this->belongsToMany(Agency::class)->withPivot(['role', 'is_active'])->withTimestamps();
    }

    public function activeAgencies(): BelongsToMany
    {
        return $this->agencies()->wherePivot('is_active', true);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret !== null;
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked' || ($this->locked_until !== null && $this->locked_until->isFuture());
    }

    public function roleIn(Agency $agency): ?string
    {
        $membership = $this->agencies()->where('agencies.id', $agency->id)->first();

        return $membership?->pivot->is_active ? $membership->pivot->role : null;
    }
}

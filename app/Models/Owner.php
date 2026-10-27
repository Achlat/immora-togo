<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Owner extends Model
{
    use BelongsToAgency, SoftDeletes;

    protected $guarded = ['id'];

    public function displayName(): string
    {
        return $this->kind === 'company'
            ? (string) $this->company_name
            : trim(($this->last_name ?? '').' '.($this->first_name ?? ''));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function units(): HasManyThrough
    {
        return $this->hasManyThrough(Unit::class, Property::class);
    }

    public function mandate(): HasOne
    {
        return $this->hasOne(Mandate::class)->where('status', 'active')->latestOfMany();
    }

    public function statements(): HasMany
    {
        return $this->hasMany(OwnerStatement::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}

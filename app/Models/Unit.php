<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use BelongsToAgency, SoftDeletes;

    public const STATUSES = [
        'vacant' => 'Vacant', 'reserved' => 'Réservé', 'occupied' => 'Occupé',
        'works' => 'En travaux', 'unavailable' => 'Indisponible',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['furnished' => 'boolean'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function activeLease(): HasOne
    {
        return $this->hasOne(Lease::class)->whereIn('status', ['active', 'notice'])->latestOfMany('start_date');
    }

    public function typeLabel(): string
    {
        return config("immora.unit_types.{$this->type}", ucfirst($this->type));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** RG-09 : statut calculé à partir des baux, sauf blocage manuel. */
    public function refreshStatus(): void
    {
        if (in_array($this->status, ['works', 'unavailable'], true)) {
            return;
        }

        $today = now()->toDateString();
        $hasActive = $this->leases()->whereIn('status', ['active', 'notice'])->where('start_date', '<=', $today)->exists();
        $hasFuture = $this->leases()->whereIn('status', ['active', 'notice'])->where('start_date', '>', $today)->exists();

        $this->update(['status' => $hasActive ? 'occupied' : ($hasFuture ? 'reserved' : 'vacant')]);
    }
}

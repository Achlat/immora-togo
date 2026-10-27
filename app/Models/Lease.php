<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lease extends Model
{
    use BelongsToAgency;

    public const STATUSES = [
        'draft' => 'Brouillon', 'active' => 'Actif', 'notice' => 'Préavis', 'terminated' => 'Résilié',
    ];

    public const TYPES = [
        'habitation' => 'Habitation', 'commercial' => 'Professionnel / commercial', 'meuble' => 'Meublé',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date', 'end_date' => 'date', 'notice_date' => 'date',
            'terminated_at' => 'date', 'activated_at' => 'datetime',
            'vat_rate' => 'decimal:2', 'penalty_rate' => 'decimal:2',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function monthlyTotal(): int
    {
        $vat = (int) round(($this->rent_amount + $this->charges_amount) * (float) $this->vat_rate / 100);

        return $this->rent_amount + $this->charges_amount + $vat;
    }
}

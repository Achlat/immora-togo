<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceLine extends Model
{
    /**
     * Types de ligne et priorité d'imputation (RG-04 : frais et pénalités d'abord).
     * « owner » = encaissé pour le compte du propriétaire.
     */
    public const TYPES = [
        'penalty' => ['label' => 'Pénalité de retard', 'priority' => 1, 'owner' => true],
        'agency_fee' => ['label' => "Frais d'agence", 'priority' => 2, 'owner' => false],
        'rent' => ['label' => 'Loyer', 'priority' => 3, 'owner' => true],
        'charges' => ['label' => 'Charges', 'priority' => 4, 'owner' => true],
        'vat' => ['label' => 'TVA', 'priority' => 5, 'owner' => false],
        'deposit' => ['label' => 'Caution', 'priority' => 6, 'owner' => false],
        'advance' => ['label' => 'Avance sur loyers', 'priority' => 7, 'owner' => false],
        'other' => ['label' => 'Autre', 'priority' => 8, 'owner' => false],
    ];

    protected $guarded = ['id'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function balance(): int
    {
        return $this->amount - $this->paid_amount;
    }

    public static function priorityFor(string $type): int
    {
        return self::TYPES[$type]['priority'] ?? 9;
    }
}

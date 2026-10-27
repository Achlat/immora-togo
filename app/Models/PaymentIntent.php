<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paiement en ligne initié (Mobile Money / carte). Volontairement hors portée globale :
 * les webhooks arrivent sans agence active.
 */
class PaymentIntent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['provider_payload' => 'array'];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class)->withoutGlobalScopes();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withoutGlobalScopes();
    }
}

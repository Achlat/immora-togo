<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use BelongsToAgency;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(Payment::class, 'reversal_of_id');
    }

    public function methodLabel(): string
    {
        $label = config("immora.payment_methods.{$this->method}", $this->method);
        if ($this->method === 'mobile_money' && $this->provider) {
            $label = config("immora.mobile_money_providers.{$this->provider}", $label);
        }

        return $this->method === 'credit' ? 'Avance / avoir' : $label;
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use BelongsToAgency, SoftDeletes;

    protected $guarded = ['id'];

    public function displayName(): string
    {
        return $this->kind === 'company'
            ? (string) $this->company_name
            : trim(($this->last_name ?? '').' '.($this->first_name ?? ''));
    }

    public function greetingName(): string
    {
        return $this->kind === 'company' ? (string) $this->company_name : (string) ($this->first_name ?: $this->last_name);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(TenantCredit::class);
    }

    public function balanceDue(): int
    {
        return (int) $this->invoices()->whereNotIn('status', ['paid', 'cancelled'])
            ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as due')->value('due');
    }

    public function availableCredit(): int
    {
        return (int) $this->credits()->sum('remaining_amount');
    }
}

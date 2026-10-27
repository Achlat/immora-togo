<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToAgency;

    public const STATUSES = [
        'due' => 'À payer', 'partial' => 'Partiellement payée', 'paid' => 'Payée',
        'overdue' => 'En retard', 'cancelled' => 'Annulée',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'due_date' => 'date', 'paid_at' => 'datetime'];
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('priority')->orderBy('id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['paid', 'cancelled']);
    }

    public function balance(): int
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function periodLabel(): string
    {
        if ($this->kind === 'entry') {
            return "Entrée dans les lieux";
        }

        return ucfirst($this->period_start->translatedFormat('F Y'));
    }

    /** Recalcule montant payé et statut à partir des lignes. */
    public function refreshTotals(): void
    {
        $this->load('lines');
        $total = (int) $this->lines->sum('amount');
        $paid = (int) $this->lines->sum('paid_amount');
        $status = match (true) {
            $total > 0 && $paid >= $total => 'paid',
            $this->due_date->isPast() && ! $this->due_date->isToday() => 'overdue',
            $paid > 0 => 'partial',
            default => 'due',
        };

        $this->fill([
            'total_amount' => $total,
            'paid_amount' => $paid,
            'status' => $status,
            'paid_at' => $status === 'paid' ? ($this->paid_at ?? $this->lastAllocationDate()) : null,
        ])->save();
    }

    /** Date du dernier règlement imputé : c'est la date « réglée le » de la quittance. */
    private function lastAllocationDate(): \Illuminate\Support\Carbon
    {
        $date = PaymentAllocation::query()->whereIn('invoice_line_id', $this->lines->pluck('id'))
            ->where('amount', '>', 0)->max('allocated_at');

        return $date !== null ? \Illuminate\Support\Carbon::parse($date) : now();
    }
}

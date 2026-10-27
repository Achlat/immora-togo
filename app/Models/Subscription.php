<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['trial_ends_at' => 'date', 'current_period_end' => 'date'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function endsAt(): ?\Illuminate\Support\Carbon
    {
        return $this->status === 'trial' ? $this->trial_ends_at : $this->current_period_end;
    }

    public function isActive(): bool
    {
        $end = $this->endsAt();

        return in_array($this->status, ['trial', 'active'], true) && ($end === null || $end->endOfDay()->isFuture());
    }

    public function daysLeft(): ?int
    {
        $end = $this->endsAt();

        return $end === null ? null : max(0, (int) now()->startOfDay()->diffInDays($end, false));
    }
}

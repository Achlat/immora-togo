<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mandate extends Model
{
    use BelongsToAgency;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'commission_vat' => 'boolean', 'tacit_renewal' => 'boolean'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function endDate(): \Illuminate\Support\Carbon
    {
        return $this->start_date->copy()->addMonths($this->duration_months)->subDay();
    }
}

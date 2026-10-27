<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToAgency;

    public const CHARGED_TO = ['owner' => 'Propriétaire', 'tenant' => 'Locataire', 'agency' => 'Agence'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['spent_on' => 'date'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function categoryLabel(): string
    {
        return config("immora.expense_categories.{$this->category}", $this->category);
    }
}

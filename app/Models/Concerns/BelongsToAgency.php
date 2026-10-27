<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Agency;
use App\Support\CurrentAgency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cloisonnement multi-agences : toute requête est filtrée sur l'agence active
 * et tout nouvel enregistrement y est rattaché (CDC chap. 22).
 */
trait BelongsToAgency
{
    public static function bootBelongsToAgency(): void
    {
        static::addGlobalScope('agency', function (Builder $builder): void {
            $current = app(CurrentAgency::class);
            if ($current->has()) {
                $builder->where($builder->getModel()->getTable().'.agency_id', $current->id());
            }
        });

        static::creating(function ($model): void {
            $current = app(CurrentAgency::class);
            if (empty($model->agency_id) && $current->has()) {
                $model->agency_id = $current->id();
            }
        });
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}

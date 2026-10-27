<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\CurrentAgency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Journal d'audit en ajout seul : aucune mise à jour ni suppression (CDC chap. 23).
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public static function record(string $action, Model $entity, array $data = []): self
    {
        return self::create([
            'agency_id' => app(CurrentAgency::class)->id() ?? $entity->getAttribute('agency_id'),
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => class_basename($entity),
            'entity_id' => $entity->getKey(),
            'data' => $data ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

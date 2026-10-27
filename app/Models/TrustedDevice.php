<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrustedDevice extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['two_factor_trusted_until' => 'datetime', 'last_seen_at' => 'datetime'];
    }
}

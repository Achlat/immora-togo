<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Numérotation continue, sans trou, par agence et par année (RG-08).
 * Le verrou de ligne garantit l'unicité sous forte charge.
 */
final class SequenceService
{
    public const PREFIXES = [
        'invoice' => 'ECH', 'receipt' => 'Q', 'payment' => 'REC', 'statement' => 'REL',
        'lease' => 'BAIL', 'property' => 'BIE', 'owner' => 'PRO', 'tenant' => 'LOC', 'mandate' => 'MAN',
    ];

    public function next(int $agencyId, string $name, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        $value = DB::transaction(function () use ($agencyId, $name, $year): int {
            DB::table('sequences')->insertOrIgnore([
                'agency_id' => $agencyId, 'name' => $name, 'year' => $year, 'last_value' => 0,
            ]);

            $row = DB::table('sequences')
                ->where(['agency_id' => $agencyId, 'name' => $name, 'year' => $year])
                ->lockForUpdate()
                ->first();

            $next = (int) $row->last_value + 1;
            DB::table('sequences')
                ->where(['agency_id' => $agencyId, 'name' => $name, 'year' => $year])
                ->update(['last_value' => $next]);

            return $next;
        });

        return sprintf('%s-%d-%06d', self::PREFIXES[$name] ?? strtoupper($name), $year, $value);
    }

    /** Référence courte sans année pour les fiches (BIE-0042). */
    public function reference(int $agencyId, string $name): string
    {
        $full = $this->next($agencyId, $name, 0);

        return sprintf('%s-%04d', self::PREFIXES[$name] ?? strtoupper($name), (int) substr($full, -6));
    }
}

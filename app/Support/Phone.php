<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalisation des numéros au format E.164 (+2250701020304).
 */
final class Phone
{
    public static function normalize(?string $input, ?string $countryCode = null): ?string
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $input);
        $trimmed = ltrim(trim($input));

        if (str_starts_with($trimmed, '+')) {
            $number = '+'.$digits;
        } elseif (str_starts_with($digits, '00')) {
            $number = '+'.substr($digits, 2);
        } else {
            $country = $countryCode ?? config('immora.default_country');
            $dial = config("immora.countries.{$country}.dial", '225');
            $number = '+'.$dial.$digits;
        }

        return preg_match('/^\+\d{8,15}$/', $number) === 1 ? $number : null;
    }

    public static function looksLikePhone(string $identifier): bool
    {
        return ! str_contains($identifier, '@') && preg_match('/\d{6,}/', preg_replace('/\D/', '', $identifier)) === 1;
    }

    /** +225 07 •• •• 03 04 */
    public static function mask(?string $e164): string
    {
        if ($e164 === null || strlen($e164) < 8) {
            return '';
        }

        return substr($e164, 0, 4).' '.substr($e164, 4, 2).' •• •• '.substr($e164, -4, 2).' '.substr($e164, -2);
    }

    /** Affichage lisible : +225 07 01 02 03 04 */
    public static function display(?string $e164): string
    {
        if ($e164 === null) {
            return '—';
        }

        foreach (config('immora.countries') as $country) {
            if (str_starts_with($e164, '+'.$country['dial'])) {
                $national = substr($e164, strlen($country['dial']) + 1);

                return '+'.$country['dial'].' '.trim(chunk_split($national, 2, ' '));
            }
        }

        return $e164;
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Montants FCFA (RG-01) : entiers, séparateur de milliers insécable, arrondi demi vers le haut.
 */
final class Money
{
    private const NBSP = "\u{00A0}";

    public static function format(int $amount, bool $withCurrency = true): string
    {
        $formatted = number_format(abs($amount), 0, ',', self::NBSP);
        $sign = $amount < 0 ? '−'.self::NBSP : '';

        return $sign.$formatted.($withCurrency ? self::NBSP.'FCFA' : '');
    }

    /** Pourcentage d'un montant, arrondi au franc (demi vers le haut). */
    public static function percent(int $amount, float|string $rate): int
    {
        return (int) round($amount * (float) $rate / 100, 0, PHP_ROUND_HALF_UP);
    }

    /** Prorata RG-02 : loyer × jours occupés ÷ jours du mois. */
    public static function prorata(int $monthlyAmount, int $occupiedDays, int $daysInMonth): int
    {
        return (int) round($monthlyAmount * $occupiedDays / $daysInMonth, 0, PHP_ROUND_HALF_UP);
    }

    /** Convertit une saisie « 1 250 000 » en entier. */
    public static function parse(string|int|null $input): int
    {
        if (is_int($input)) {
            return $input;
        }

        return (int) preg_replace('/[^\d-]/', '', (string) $input);
    }
}

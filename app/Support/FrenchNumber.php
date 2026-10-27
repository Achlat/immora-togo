<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Montant en lettres pour les documents légaux (« cent cinquante mille francs CFA »).
 * Orthographe traditionnelle : traits d'union entre dizaines et unités, « et » pour 21, 31… 71.
 */
final class FrenchNumber
{
    private const UNITS = ['zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix',
        'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];

    private const TENS = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante'];

    public static function amountInWords(int $amount): string
    {
        return self::toWords($amount).' francs CFA';
    }

    public static function toWords(int $number): string
    {
        if ($number < 0) {
            return 'moins '.self::toWords(-$number);
        }
        if ($number === 0) {
            return self::UNITS[0];
        }

        $parts = [];
        foreach ([1_000_000_000 => 'milliard', 1_000_000 => 'million'] as $value => $word) {
            if ($number >= $value) {
                $count = intdiv($number, $value);
                $parts[] = self::belowThousand($count, true).' '.$word.($count > 1 ? 's' : '');
                $number %= $value;
            }
        }
        if ($number >= 1000) {
            $thousands = intdiv($number, 1000);
            $parts[] = $thousands === 1 ? 'mille' : self::belowThousand($thousands, false).' mille';
            $number %= 1000;
        }
        if ($number > 0) {
            $parts[] = self::belowThousand($number, true);
        }

        return implode(' ', $parts);
    }

    private static function belowThousand(int $number, bool $isFinal): string
    {
        $hundreds = intdiv($number, 100);
        $rest = $number % 100;
        $words = [];

        if ($hundreds > 0) {
            $hundredWord = $hundreds === 1 ? 'cent' : self::UNITS[$hundreds].' cent';
            if ($hundreds > 1 && $rest === 0 && $isFinal) {
                $hundredWord .= 's';
            }
            $words[] = $hundredWord;
        }
        if ($rest > 0) {
            $words[] = self::belowHundred($rest, $isFinal);
        }

        return implode(' ', $words);
    }

    private static function belowHundred(int $number, bool $isFinal): string
    {
        if ($number < 20) {
            return self::UNITS[$number];
        }

        $ten = intdiv($number, 10);
        $unit = $number % 10;

        if ($ten === 7 || $ten === 9) {
            $base = $ten === 7 ? 'soixante' : 'quatre-vingt';
            $tail = self::UNITS[10 + $unit];

            return $ten === 7 && $unit === 1 ? $base.' et '.$tail : $base.'-'.$tail;
        }
        if ($ten === 8) {
            return $unit === 0 ? 'quatre-vingt'.($isFinal ? 's' : '') : 'quatre-vingt-'.self::UNITS[$unit];
        }

        $base = self::TENS[$ten];
        if ($unit === 0) {
            return $base;
        }

        return $unit === 1 ? $base.' et un' : $base.'-'.self::UNITS[$unit];
    }
}

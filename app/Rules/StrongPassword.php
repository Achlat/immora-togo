<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

/**
 * Politique de mot de passe (CDC § 8.6) : 10 à 128 caractères, variété suffisante
 * (ou phrase de passe longue), absent des fuites connues, sans données personnelles.
 */
final class StrongPassword implements ValidationRule
{
    /** @param list<string|null> $personal nom, prénom, e-mail, nom d'agence… */
    public function __construct(private readonly array $personal = []) {}

    /** @return list<mixed> */
    public static function rules(array $personal = []): array
    {
        return ['required', 'string', 'confirmed', Password::min(10)->max(128)->uncompromised(), new self($personal)];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = (string) $value;
        $lower = mb_strtolower($password);

        foreach ($this->personal as $word) {
            $word = mb_strtolower(trim((string) $word));
            if ($word !== '' && mb_strlen($word) >= 3 && str_contains($lower, explode('@', $word)[0])) {
                $fail('Le mot de passe ne doit pas contenir votre nom, votre e-mail ou le nom de votre agence.');

                return;
            }
        }

        $classes = (int) preg_match('/[a-z]/', $password) + (int) preg_match('/[A-Z]/', $password)
            + (int) preg_match('/\d/', $password) + (int) preg_match('/[^a-zA-Z\d]/', $password);

        if (mb_strlen($password) < 16 && $classes < 3) {
            $fail('Mot de passe trop simple : mélangez majuscules, minuscules, chiffres et symboles, ou utilisez une phrase de 16 caractères ou plus.');
        }
    }
}

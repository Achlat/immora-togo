<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Agency;
use App\Models\User;
use App\Services\Messaging\Notifier;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Ouverture de l'espace en ligne d'un propriétaire ou d'un locataire (PORT-01, AUTH-10) :
 * compte rattaché au numéro de téléphone, connexion par code SMS.
 */
final class PortalAccess
{
    public function __construct(private readonly Notifier $notifier) {}

    public function grant(Agency $agency, string $role, ?string $phone, string $firstName, string $lastName, ?string $email): User
    {
        if ($phone === null) {
            throw new DomainException("Renseignez d'abord un numéro de téléphone : l'accès se fait par code SMS.");
        }

        return DB::transaction(function () use ($agency, $role, $phone, $firstName, $lastName, $email): User {
            $user = User::query()->where('phone_e164', $phone)->first() ?? User::create([
                'first_name' => $firstName ?: '—',
                'last_name' => $lastName ?: '—',
                'phone_e164' => $phone,
                'email' => $email !== null && ! User::query()->where('email', $email)->exists() ? $email : null,
            ]);

            $existingRole = $user->roleIn($agency);
            if ($existingRole !== null && ! in_array($existingRole, ['owner', 'tenant'], true)) {
                throw new DomainException('Ce numéro appartient déjà à un collaborateur de l\'agence.');
            }

            $agency->users()->syncWithoutDetaching([$user->id => ['role' => $role, 'is_active' => true]]);
            $this->notifier->portalAccess($phone, $firstName ?: $lastName, $agency, route('login', ['mode' => 'sms']));

            return $user;
        });
    }
}

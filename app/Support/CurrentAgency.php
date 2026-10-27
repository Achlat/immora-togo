<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Agency;
use RuntimeException;

/**
 * Agence active pour la requête en cours. Alimentée par le middleware ResolveAgency.
 */
final class CurrentAgency
{
    private ?Agency $agency = null;

    private ?string $role = null;

    public function set(Agency $agency, string $role): void
    {
        $this->agency = $agency;
        $this->role = $role;
    }

    public function clear(): void
    {
        $this->agency = null;
        $this->role = null;
    }

    public function has(): bool
    {
        return $this->agency !== null;
    }

    public function get(): Agency
    {
        return $this->agency ?? throw new RuntimeException('Aucune agence active.');
    }

    public function id(): ?int
    {
        return $this->agency?->id;
    }

    public function role(): ?string
    {
        return $this->role;
    }

    public function can(string $permission): bool
    {
        // Les noms de permission contiennent un point : lecture directe, pas de notation pointée.
        $roles = config('immora.permissions')[$permission] ?? [];

        return $this->role !== null && in_array($this->role, $roles, true);
    }
}

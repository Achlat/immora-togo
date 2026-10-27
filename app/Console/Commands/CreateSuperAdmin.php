<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/** Crée le compte super-administrateur de l'éditeur. */
final class CreateSuperAdmin extends Command
{
    protected $signature = 'immora:super-admin {email} {--first=Équipe} {--last=Immora}';

    protected $description = 'Crée ou promeut un compte super-administrateur (console éditeur)';

    public function handle(): int
    {
        $password = (string) $this->secret('Mot de passe (12 caractères minimum)');
        if (mb_strlen($password) < 12) {
            $this->error('Mot de passe trop court.');

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => mb_strtolower((string) $this->argument('email'))]);
        $user->fill([
            'first_name' => $user->first_name ?? $this->option('first'),
            'last_name' => $user->last_name ?? $this->option('last'),
            'password' => $password,
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ])->save();

        $this->info("Super-administrateur prêt : {$user->email}");

        return self::SUCCESS;
    }
}

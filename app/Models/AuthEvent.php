<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthEvent extends Model
{
    public const UPDATED_AT = null;

    public const LABELS = [
        'login' => 'Connexion réussie', 'login_failed' => 'Échec de connexion', 'locked' => 'Compte verrouillé',
        'otp_sent' => 'Code SMS envoyé', 'otp_login' => 'Connexion par code SMS', 'two_factor_ok' => 'Double authentification validée',
        'two_factor_failed' => 'Échec double authentification', 'two_factor_enabled' => 'Double authentification activée',
        'password_reset' => 'Mot de passe réinitialisé', 'new_device' => 'Nouvel appareil', 'logout' => 'Déconnexion',
        'sessions_revoked' => 'Sessions révoquées', 'invitation_accepted' => 'Invitation acceptée',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public static function log(string $type, ?int $userId, array $meta = [], ?int $agencyId = null): void
    {
        self::create([
            'user_id' => $userId,
            'agency_id' => $agencyId,
            'type' => $type,
            'ip_address' => request()?->ip(),
            'user_agent' => mb_substr((string) request()?->userAgent(), 0, 255),
            'meta' => $meta ?: null,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return self::LABELS[$this->type] ?? $this->type;
    }
}

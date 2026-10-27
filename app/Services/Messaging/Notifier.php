<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Models\Agency;
use App\Models\Invoice;
use App\Models\MessageLog;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\Mail;

/**
 * Messages transactionnels (CDC chap. 8.7 et 19). Le SMS est prioritaire, l'e-mail sert de secours.
 */
final class Notifier
{
    public function __construct(private readonly SmsSender $sms) {}

    public function otp(User $user, string $code): void
    {
        $body = "Immora : votre code de connexion est {$code}. Il expire dans 5 minutes. Ne le partagez avec personne.";
        $this->toUser($user, 'otp', 'Votre code de connexion Immora', $body);
    }

    public function newDevice(User $user, string $device): void
    {
        $when = now()->format('d/m \à H\hi');
        $body = "Immora : nouvelle connexion à votre compte depuis {$device}, le {$when}. Ce n'était pas vous ? Changez votre mot de passe sans attendre.";
        $this->toUser($user, 'new_device', 'Nouvelle connexion à votre compte', $body);
    }

    public function accountLocked(User $admin, User $locked): void
    {
        $body = "Immora : le compte de {$locked->fullName()} a été verrouillé 1 h après 10 tentatives de connexion échouées.";
        $this->toUser($admin, 'account_locked', 'Alerte de sécurité', $body);
    }

    public function passwordReset(User $user, string $url): void
    {
        $body = "Immora : pour choisir un nouveau mot de passe, ouvrez ce lien (valable 30 minutes) : {$url}";
        $this->toUser($user, 'password_reset', 'Réinitialisation de votre mot de passe', $body);
    }

    public function invitation(string $recipient, bool $isPhone, string $inviter, string $agency, string $url, ?int $agencyId): void
    {
        $body = "{$inviter} vous invite à rejoindre {$agency} sur Immora. Créez votre accès (lien valable 72 h) : {$url}";
        $isPhone
            ? $this->sms->send($recipient, $body, 'invitation', $agencyId)
            : $this->mail($recipient, "Invitation à rejoindre {$agency}", $body, 'invitation', $agencyId);
    }

    public function paymentReceived(Payment $payment, ?Invoice $invoice, Agency $agency, string $receiptUrl): void
    {
        $tenant = $payment->tenant;
        $phone = $tenant->whatsapp_e164 ?: $tenant->phone_e164;
        if ($phone === null) {
            return;
        }

        $period = $invoice !== null ? ' pour '.mb_strtolower($invoice->periodLabel()) : '';
        $body = "Bonjour {$tenant->greetingName()}, nous avons bien reçu votre paiement de ".Money::format($payment->amount)
            ." ({$payment->methodLabel()}){$period}. Votre reçu : {$receiptUrl} — {$agency->name}";

        $this->sms->send($phone, $body, 'payment_received', $agency->id);
    }

    public function portalAccess(string $phone, string $name, Agency $agency, string $url): void
    {
        $body = "Bonjour {$name}, {$agency->name} vous ouvre votre espace en ligne : loyers, quittances et paiement Mobile Money. Connectez-vous avec ce numéro : {$url}";
        $this->sms->send($phone, $body, 'portal_access', $agency->id);
    }

    private function toUser(User $user, string $template, string $subject, string $body): void
    {
        if ($user->phone_e164 !== null) {
            $this->sms->send($user->phone_e164, $body, $template, $user->last_agency_id);

            return;
        }
        if ($user->email !== null) {
            $this->mail($user->email, $subject, $body, $template, $user->last_agency_id);
        }
    }

    private function mail(string $email, string $subject, string $body, string $template, ?int $agencyId): void
    {
        Mail::raw($body, fn ($message) => $message->to($email)->subject($subject));
        MessageLog::create([
            'agency_id' => $agencyId, 'channel' => 'email', 'recipient' => $email,
            'template' => $template, 'body' => $body, 'status' => 'sent',
        ]);
    }
}

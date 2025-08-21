<?php

namespace App\Services;

use App\Models\User;
use App\Models\Trainer;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\TrainerWelcomeMail;
use Illuminate\Support\Facades\Log;

class TrainerWelcomeService
{
    /**
     * Envoie un email de bienvenue avec le lien de réinitialisation de mot de passe
     *
     * @param Trainer $trainer
     * @param User $user
     * @return bool
     */
    public function sendWelcomeEmail(Trainer $trainer, User $user): bool
    {
        try {
            // Générer le token de réinitialisation de mot de passe
            $token = Password::createToken($user);

            // Générer le lien de réinitialisation
            $resetUrl = config('app.frontend_url') . '/forgot-password?token=' . $token . '&email=' . urlencode($user->email);

            // Alternativement, si vous utilisez les routes Laravel :
            // $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));

            // Envoyer l'email
            Mail::to($user->email)->send(new TrainerWelcomeMail($trainer, $user, $resetUrl));

            Log::info("Email de bienvenue envoyé avec succès à {$user->email}");

            return true;

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de l'email de bienvenue à {$user->email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie un email de bienvenue en utilisant seulement l'ID du formateur
     *
     * @param int $trainerId
     * @return bool
     */
    public function sendWelcomeEmailById(int $trainerId): bool
    {
        try {
            $trainer = Trainer::with('user')->findOrFail($trainerId);
            return $this->sendWelcomeEmail($trainer, $trainer->user);
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de l'email pour le formateur ID {$trainerId}: " . $e->getMessage());
            return false;
        }
    }
}

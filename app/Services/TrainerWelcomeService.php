<?php

namespace App\Services;

use App\Models\User;
use App\Models\Trainer;
use Illuminate\Support\Facades\Mail;
use App\Mail\TrainerWelcomeMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class TrainerWelcomeService
{
    /**
     * Envoie un email de bienvenue avec le mot de passe généré
     *
     * @param Trainer $trainer
     * @param User $user
     * @param string $password
     * @return bool
     */
    public function sendWelcomeEmail(Trainer $trainer, User $user, string $password): bool
    {
        try {
            // Envoyer l'email avec le mot de passe
            Mail::to($user->email)->send(new TrainerWelcomeMail($trainer, $user, $password));

            Log::info("Email de bienvenue envoyé avec succès à {$user->email}");

            return true;

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de l'email de bienvenue à {$user->email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie un email de bienvenue en utilisant seulement l'ID du formateur
     * Note: Cette méthode ne peut pas générer un nouveau mot de passe
     * car elle ne connaît pas le mot de passe original
     *
     * @param int $trainerId
     * @return bool
     */
    public function sendWelcomeEmailById(int $trainerId): bool
    {
        try {
            $trainer = Trainer::with('user')->findOrFail($trainerId);

            // Générer un nouveau mot de passe temporaire
            $newPassword = str()->random(8);

            // Mettre à jour le mot de passe de l'utilisateur
            $trainer->user->update([
                'password' => Hash::make($newPassword)
            ]);

            return $this->sendWelcomeEmail($trainer, $trainer->user, $newPassword);
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de l'email pour le formateur ID {$trainerId}: " . $e->getMessage());
            return false;
        }
    }
}

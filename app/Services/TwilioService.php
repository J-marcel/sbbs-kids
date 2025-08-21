<?php

// namespace App\Services;

// use App\Models\User;
// use Twilio\Rest\Client;
// use Exception;
// use Illuminate\Support\Facades\Log;

// class TwilioService
// {
//     protected Client $twilio;
//     protected string $twilioNumber;

//     public function __construct()
//     {
//         $this->twilio = new Client(
//             config('services.twilio.sid'),
//             config('services.twilio.token')
//         );
//         $this->twilioNumber = config('services.twilio.phone_number');
//     }

//     /**
//      * Envoyer un OTP par SMS
//      */
//     public function sendSMS(User $user, string $otp): bool
//     {
//         try {
//             $message = "Votre code de vérification " . config('app.name') . " est : {$otp}. Ce code expire dans 15 minutes.";

//             $this->twilio->messages->create(
//                 $user->phone_number, // Numéro du destinataire
//                 [
//                     'from' => $this->twilioNumber,
//                     'body' => $message
//                 ]
//             );

//             Log::info("SMS OTP envoyé avec succès à {$user->phone_number}");
//             return true;

//         } catch (Exception $e) {
//             Log::error("Erreur envoi SMS OTP: " . $e->getMessage());
//             return false;
//         }
//     }

//     /**
//      * Envoyer un OTP par WhatsApp
//      */
//     public function sendWhatsApp(User $user, string $otp): bool
//     {
//         try {
//             $message = "🔐 Votre code de vérification " . config('app.name') . " est :\n\n*{$otp}*\n\nCe code expire dans 15 minutes.\n\n_Ne partagez ce code avec personne._";

//             $this->twilio->messages->create(
//                 "whatsapp:{$user->number_whatsapp}", // Format WhatsApp
//                 [
//                     'from' => "whatsapp:{$this->twilioNumber}",
//                     'body' => $message
//                 ]
//             );

//             Log::info("WhatsApp OTP envoyé avec succès à {$user->number_whatsapp}");
//             return true;

//         } catch (Exception $e) {
//             Log::error("Erreur envoi WhatsApp OTP: " . $e->getMessage());
//             return false;
//         }
//     }

//     /**
//      * Envoyer un OTP par tous les canaux disponibles
//      */
//     public function sendOtpMultiChannel(User $user, string $otp): array
//     {
//         $results = [
//             'sms' => false,
//             'whatsapp' => false
//         ];

//         // Envoyer par SMS si numéro de téléphone disponible
//         if ($user->phone_number) {
//             $results['sms'] = $this->sendSMS($user, $otp);
//         }

//         // Envoyer par WhatsApp si numéro WhatsApp disponible
//         if ($user->number_whatsapp) {
//             $results['whatsapp'] = $this->sendWhatsApp($user, $otp);
//         }

//         return $results;
//     }
// }


namespace App\Services;

use App\Models\User;
use Twilio\Rest\Client;
use Exception;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected Client $twilio;
    protected string $twilioNumber;
    protected string $whatsappNumber;

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $this->twilioNumber = config('services.twilio.phone_number');

        // Numéro WhatsApp (peut être différent du SMS)
        $this->whatsappNumber = config('services.twilio.whatsapp_number', $this->twilioNumber);
    }

    /**
     * Vérifier si le service SMS est disponible
     */
    public function isSmsEnabled(): bool
    {
        return !empty($this->twilioNumber) && config('services.twilio.sms_enabled', true);
    }

    /**
     * Vérifier si le service WhatsApp est disponible
     */
    public function isWhatsAppEnabled(): bool
    {
        return !empty($this->whatsappNumber) && config('services.twilio.whatsapp_enabled', false);
    }

    /**
     * Envoyer un OTP par SMS
     */
    public function sendSMS(User $user, string $otp): bool
    {
        if (!$this->isSmsEnabled()) {
            Log::warning("SMS désactivé dans la configuration");
            return false;
        }

        if (!$user->phone_number) {
            Log::warning("Numéro de téléphone manquant pour l'utilisateur {$user->id}");
            return false;
        }

        try {
            $message = "Votre code de vérification " . config('app.name') . " est : {$otp}. Ce code expire dans 15 minutes.";

            // Nettoyer le numéro de téléphone
            $phoneNumber = $this->formatPhoneNumber($user->phone_number);

            Log::info("Tentative d'envoi SMS", [
                'to' => $phoneNumber,
                'from' => $this->twilioNumber,
                'user_id' => $user->id
            ]);

            $message = $this->twilio->messages->create(
                $phoneNumber,
                [
                    'from' => $this->twilioNumber,
                    'body' => $message
                ]
            );

            Log::info("SMS OTP envoyé avec succès", [
                'to' => $phoneNumber,
                'message_sid' => $message->sid,
                'user_id' => $user->id
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Erreur envoi SMS OTP", [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'phone' => $user->phone_number,
                'from' => $this->twilioNumber
            ]);
            return false;
        }
    }

    /**
     * Envoyer un OTP par WhatsApp
     */
    public function sendWhatsApp(User $user, string $otp): bool
    {
        if (!$this->isWhatsAppEnabled()) {
            Log::warning("WhatsApp désactivé dans la configuration");
            return false;
        }

        if (!$user->number_whatsapp) {
            Log::warning("Numéro WhatsApp manquant pour l'utilisateur {$user->id}");
            return false;
        }

        try {
            $message = "🔐 Votre code de vérification " . config('app.name') . " est :\n\n*{$otp}*\n\nCe code expire dans 15 minutes.\n\n_Ne partagez ce code avec personne._";

            // Nettoyer le numéro WhatsApp
            $whatsappNumber = $this->formatPhoneNumber($user->number_whatsapp);

            Log::info("Tentative d'envoi WhatsApp", [
                'to' => "whatsapp:{$whatsappNumber}",
                'from' => "whatsapp:{$this->whatsappNumber}",
                'user_id' => $user->id
            ]);

            $message = $this->twilio->messages->create(
                "whatsapp:{$whatsappNumber}",
                [
                    'from' => "whatsapp:{$this->whatsappNumber}",
                    'body' => $message
                ]
            );

            Log::info("WhatsApp OTP envoyé avec succès", [
                'to' => "whatsapp:{$whatsappNumber}",
                'message_sid' => $message->sid,
                'user_id' => $user->id
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Erreur envoi WhatsApp OTP", [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'whatsapp' => $user->number_whatsapp,
                'from' => "whatsapp:{$this->whatsappNumber}"
            ]);
            return false;
        }
    }

    /**
     * Envoyer un OTP par tous les canaux disponibles
     */
    public function sendOtpMultiChannel(User $user, string $otp): array
    {
        $results = [
            'sms' => false,
            'whatsapp' => false
        ];

        // Envoyer par SMS si activé et numéro disponible
        if ($this->isSmsEnabled() && $user->phone_number) {
            $results['sms'] = $this->sendSMS($user, $otp);
        }

        // Envoyer par WhatsApp si activé et numéro disponible
        if ($this->isWhatsAppEnabled() && $user->number_whatsapp) {
            $results['whatsapp'] = $this->sendWhatsApp($user, $otp);
        }

        return $results;
    }

    /**
     * Formater le numéro de téléphone au format international
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Supprimer tous les espaces et caractères non numériques sauf le +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // S'assurer que le numéro commence par +
        if (!str_starts_with($phone, '+')) {
            // Si le numéro commence par 0, le remplacer par le code pays (exemple pour la France +33)
            if (str_starts_with($phone, '0')) {
                $phone = '+33' . substr($phone, 1);
            } else {
                // Ajouter un code pays par défaut si nécessaire
                $phone = '+' . $phone;
            }
        }

        return $phone;
    }

    /**
     * Tester la connexion Twilio
     */
    public function testConnection(): array
    {
        try {
            // Récupérer les informations du compte
            $account = $this->twilio->api->accounts(config('services.twilio.sid'))->fetch();

            return [
                'success' => true,
                'account_sid' => $account->sid,
                'account_status' => $account->status,
                'account_name' => $account->friendlyName
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lister les numéros de téléphone disponibles
     */
    public function getAvailablePhoneNumbers(): array
    {
        try {
            $phoneNumbers = $this->twilio->incomingPhoneNumbers->read();

            $numbers = [];
            foreach ($phoneNumbers as $number) {
                $numbers[] = [
                    'phone_number' => $number->phoneNumber,
                    'friendly_name' => $number->friendlyName,
                    'capabilities' => $number->capabilities,
                    'sms_enabled' => $number->capabilities['sms'] ?? false,
                    'voice_enabled' => $number->capabilities['voice'] ?? false
                ];
            }

            return [
                'success' => true,
                'numbers' => $numbers
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

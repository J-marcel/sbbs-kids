<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InfobipService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $senderName;
    protected string $whatsappSender;

    public function __construct()
    {
        $this->baseUrl = config('services.infobip.base_url');
        $this->apiKey = config('services.infobip.api_key');
        $this->senderName = config('services.infobip.sender_name', 'YourAppName');
        $this->whatsappSender = config('services.infobip.whatsapp_sender');

        // Debug des configurations
        Log::info("Infobip Configuration:", [
            'base_url' => $this->baseUrl,
            'api_key_length' => strlen($this->apiKey ?? ''),
            'sender_name' => $this->senderName,
            'whatsapp_sender' => $this->whatsappSender
        ]);
    }

    /**
     * Tester la connectivité avec l'API Infobip
     */
    public function testConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->get($this->baseUrl . '/account/1/balance');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connexion réussie',
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur de connexion',
                'error' => $response->body(),
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception lors du test',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer un SMS via Infobip
     */
    public function sendSMS(User $user, string $message): bool
    {
        if (empty($user->phone_number)) {
            Log::warning("Numéro de téléphone manquant pour l'utilisateur {$user->id}");
            return false;
        }

        try {
            $phoneNumber = $this->formatPhoneNumber($user->phone_number);

            Log::info("Tentative d'envoi SMS", [
                'user_id' => $user->id,
                'phone_original' => $user->phone_number,
                'phone_formatted' => $phoneNumber,
                'sender' => $this->senderName
            ]);

            $payload = [
                'messages' => [
                    [
                        'destinations' => [
                            ['to' => $phoneNumber]
                        ],
                        'from' => $this->senderName,
                        'text' => $message
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'App ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($this->baseUrl . '/sms/2/text/advanced', $payload);

            Log::info("Réponse Infobip SMS", [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $status = $data['messages'][0]['status']['groupName'] ?? '';

                if (in_array($status, ['PENDING', 'SENT'])) {
                    Log::info("SMS envoyé avec succès à {$phoneNumber} via Infobip");
                    return true;
                }

                Log::error("Infobip SMS status non valide: " . json_encode($data));
                return false;
            }

            Log::error("Erreur Infobip SMS: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("Exception Infobip SMS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer un message WhatsApp via Infobip
     */
    public function sendWhatsApp(User $user, string $message): bool
    {
        if (empty($user->number_whatsapp)) {
            Log::warning("Numéro WhatsApp manquant pour l'utilisateur {$user->id}");
            return false;
        }

        try {
            $phoneNumber = $this->formatPhoneNumber($user->number_whatsapp);

            Log::info("Tentative d'envoi WhatsApp", [
                'user_id' => $user->id,
                'whatsapp_original' => $user->number_whatsapp,
                'whatsapp_formatted' => $phoneNumber,
                'sender' => $this->whatsappSender
            ]);

            $payload = [
                'from' => $this->whatsappSender,
                'to' => $phoneNumber,
                'content' => [
                    'text' => $message
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'App ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($this->baseUrl . '/whatsapp/1/message/text', $payload);

            Log::info("Réponse Infobip WhatsApp", [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp envoyé avec succès à {$phoneNumber} via Infobip");
                return true;
            }

            Log::error("Erreur Infobip WhatsApp: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("Exception Infobip WhatsApp: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer un OTP par SMS
     */
    public function sendOtpSms(User $user, string $otp): bool
    {
        $message = "Votre code de vérification {$this->senderName} est : {$otp}\n\n";
        $message .= "Ce code expire dans 15 minutes.\n";
        $message .= "Ne partagez ce code avec personne.";

        return $this->sendSMS($user, $message);
    }

    /**
     * Envoyer un OTP par WhatsApp
     */
    public function sendOtpWhatsApp(User $user, string $otp): bool
    {
        $message = "🔐 *Code de vérification {$this->senderName}*\n\n";
        $message .= "Votre code est : *{$otp}*\n\n";
        $message .= "⏱️ _Ce code expire dans 15 minutes_\n";
        $message .= "🚫 _Ne partagez ce code avec personne_";

        return $this->sendWhatsApp($user, $message);
    }

    /**
     * Envoyer OTP par plusieurs canaux
     */
    public function sendOtpMultiChannel(User $user, string $otp): array
    {
        $results = [
            'sms' => false,
            'whatsapp' => false
        ];

        // Envoi par SMS
        if (!empty($user->phone_number)) {
            $results['sms'] = $this->sendOtpSms($user, $otp);
        }

        // Envoi par WhatsApp
        if (!empty($user->number_whatsapp)) {
            $results['whatsapp'] = $this->sendOtpWhatsApp($user, $otp);
        }

        return $results;
    }

    /**
     * Formater le numéro de téléphone au format international
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Supprimer tous les espaces, tirets et caractères spéciaux
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        Log::info("Formatage numéro", [
            'original' => $phoneNumber,
            'after_cleanup' => $phoneNumber
        ]);

        // Si le numéro commence par 0, on suppose que c'est un numéro ivoirien
        if (strpos($phoneNumber, '0') === 0) {
            // Remplacer le 0 initial par +225 (code Côte d'Ivoire)
            $phoneNumber = '+225' . substr($phoneNumber, 1);
        }

        // Si le numéro ne commence pas par +, ajouter +
        if (strpos($phoneNumber, '+') !== 0) {
            // Si le numéro commence par 225, ajouter juste le +
            if (strpos($phoneNumber, '225') === 0) {
                $phoneNumber = '+' . $phoneNumber;
            } else {
                // Sinon, supposer que c'est un numéro ivoirien et ajouter +225
                $phoneNumber = '+225' . $phoneNumber;
            }
        }

        Log::info("Numéro formaté final: {$phoneNumber}");
        return $phoneNumber;
    }
}

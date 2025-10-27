<?php

namespace App\Services;

use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected TwilioService $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    /**
     * Envoyer OTP par email uniquement (méthode existante)
     */
    public function sendOtp(User $user): void
    {
        $otp = $user->generateOTP();
        Mail::to($user->email)->send(new OtpMail($otp, $user));
    }

    /**
     * Envoyer OTP par tous les canaux disponibles
     */
    public function sendOtpMultiChannel(User $user): array
    {
        $otp = $user->generateOTP();

        $results = [
            'email' => false,
            'sms' => false,
            'whatsapp' => false,
            'otp_sent' => false
        ];

        // Envoyer par email
        try {
            Mail::to($user->email)->send(new OtpMail($otp, $user));
            $results['email'] = true;
            Log::info("Email OTP envoyé avec succès à {$user->email}");
        } catch (\Exception $e) {
            Log::error("Erreur envoi email OTP: " . $e->getMessage());
        }

        // Envoyer par SMS et WhatsApp via Twilio
        $twilioResults = $this->twilioService->sendOtpMultiChannel($user, $otp);
        $results['sms'] = $twilioResults['sms'];
        $results['whatsapp'] = $twilioResults['whatsapp'];

        // Vérifier si au moins un canal a fonctionné
        $results['otp_sent'] = $results['email'] || $results['sms'] || $results['whatsapp'];

        return $results;
    }

    /**
     * Envoyer OTP par SMS uniquement
     */
    public function sendOtpSms(User $user): bool
    {
        $otp = $user->generateOTP();
        return $this->twilioService->sendSMS($user, $otp);
    }

    /**
     * Envoyer OTP par WhatsApp uniquement
     */
    public function sendOtpWhatsApp(User $user): bool
    {
        $otp = $user->generateOTP();
        return $this->twilioService->sendWhatsApp($user, $otp);
    }
}

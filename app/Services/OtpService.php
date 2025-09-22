<?php



namespace App\Services;

use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected InfobipService $infobipService;

    public function __construct(InfobipService $infobipService)
    {
        $this->infobipService = $infobipService;
    }

    /**
     * Envoyer OTP par les méthodes spécifiées
     */
    public function sendOtp(User $user, array $methods = ['email']): array
    {
        $otp = $user->generateOTP();

        $results = [];

        foreach ($methods as $method) {
            switch ($method) {
                case 'email':
                    $results['email'] = $this->sendOtpEmail($user, $otp);
                    break;
                case 'sms':
                    $results['sms'] = $this->infobipService->sendOtpSms($user, $otp);
                    break;
                case 'whatsapp':
                    $results['whatsapp'] = $this->infobipService->sendOtpWhatsApp($user, $otp);
                    break;
            }
        }

        return $results;
    }

    /**
     * Envoyer OTP par email uniquement
     */
    protected function sendOtpEmail(User $user, string $otp): bool
    {
        try {
            Mail::to($user->email)->send(new OtpMail($otp, $user));
            Log::info("Email OTP envoyé avec succès à {$user->email}");
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur envoi email OTP: " . $e->getMessage());
            return false;
        }
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
        $results['email'] = $this->sendOtpEmail($user, $otp);

        // Envoyer par SMS et WhatsApp via Infobip
        $infobipResults = $this->infobipService->sendOtpMultiChannel($user, $otp);
        $results['sms'] = $infobipResults['sms'];
        $results['whatsapp'] = $infobipResults['whatsapp'];

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
        return $this->infobipService->sendOtpSms($user, $otp);
    }

    /**
     * Envoyer OTP par WhatsApp uniquement
     */
    public function sendOtpWhatsApp(User $user): bool
    {
        $otp = $user->generateOTP();
        return $this->infobipService->sendOtpWhatsApp($user, $otp);
    }
}

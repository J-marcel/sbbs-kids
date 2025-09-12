<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SotreVerifyOtpRequest;
use App\Http\Requests\Auth\SotreResendOtpRequest;
use App\Services\OtpService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class VerifyOtpController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function verify(SotreVerifyOtpRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
                'status' => '404'
            ], 404);
        }

        $otp = $validated['otp'];

        if ($user->otp_expires_at < now()) {
            return response()->json([
                'message' => 'Le code OTP a expiré',
                'status' => '400'
            ], 400);
        }

        if (Hash::check($otp, $user->otp)) {
            $user->update([
                'is_otp_verified' => true,
                'email_verified_at' => now(),
                'otp' => null,
                'otp_expires_at' => null,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'OTP vérifié avec succès',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ? $user->role->name : null, // Ajouter le nom du rôle
                    // autres attributs si besoin
                ],
                'token' => $token,
                'status' => '200'
            ], 200);
        } else {
            return response()->json([
                'message' => 'OTP incorrect',
                'status' => '400'
            ], 400);
        }
    }

    public function resend(SotreResendOtpRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
                'status' => '404'
            ], 404);
        }

        // Renvoyer OTP par tous les canaux
        $results = $this->otpService->sendOtpMultiChannel($user);

        if (!$results['otp_sent']) {
            return response()->json([
                'message' => 'Erreur lors du renvoi de l\'OTP. Veuillez réessayer.',
                'status' => '500'
            ], 500);
        }

        $channels = [];
        if ($results['email']) $channels[] = 'email';
        if ($results['sms']) $channels[] = 'SMS';
        if ($results['whatsapp']) $channels[] = 'WhatsApp';

        return response()->json([
            'message' => 'OTP renvoyé avec succès via : ' . implode(', ', $channels),
            'delivery_status' => $results,
            'status' => '200'
        ], 200);
    }

    /**
     * Nouveau endpoint pour renvoyer OTP par SMS uniquement
     */
    public function resendSms(SotreResendOtpRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
                'status' => '404'
            ], 404);
        }

        if (!$user->phone_number) {
            return response()->json([
                'message' => 'Numéro de téléphone non disponible',
                'status' => '400'
            ], 400);
        }

        $success = $this->otpService->sendOtpSms($user);

        return response()->json([
            'message' => $success ? 'OTP envoyé par SMS avec succès' : 'Erreur lors de l\'envoi par SMS',
            'status' => $success ? '200' : '500'
        ], $success ? 200 : 500);
    }

    /**
     * Nouveau endpoint pour renvoyer OTP par WhatsApp uniquement
     */
    public function resendWhatsApp(SotreResendOtpRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
                'status' => '404'
            ], 404);
        }

        if (!$user->number_whatsapp) {
            return response()->json([
                'message' => 'Numéro WhatsApp non disponible',
                'status' => '400'
            ], 400);
        }

        $success = $this->otpService->sendOtpWhatsApp($user);

        return response()->json([
            'message' => $success ? 'OTP envoyé par WhatsApp avec succès' : 'Erreur lors de l\'envoi par WhatsApp',
            'status' => $success ? '200' : '500'
        ], $success ? 200 : 500);
    }
}

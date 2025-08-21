<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreLoginRequest;
use App\Services\OtpService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function login(StoreLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Identifiants incorrects.',
                'status' => 400,
            ], 400);
        }

        if (!$user->compte_active) {
            return response()->json([
                'message' => 'Votre compte a été banni. Veuillez contacter le support.',
                'status' => 403,
            ], 403);
        }

        // Vérifie si l'email est confirmé
        if (is_null($user->email_verified_at)) {
            // Déterminer les méthodes d'envoi disponibles
            $methods = ['email'];
            if ($user->phone_number) $methods[] = 'sms';
            if ($user->number_whatsapp) $methods[] = 'whatsapp';

            $results = $this->otpService->sendOtp($user, $methods);

            $sentMethods = array_keys(array_filter($results));
            $methodsText = implode(', ', array_map(function($method) {
                return match($method) {
                    'email' => 'email',
                    'sms' => 'SMS',
                    'whatsapp' => 'WhatsApp'
                };
            }, $sentMethods));

            return response()->json([
                'message' => "Votre compte n'est pas encore confirmé. Un code OTP a été envoyé via {$methodsText}.",
                'sent_via' => $results,
                'status' => 200,
            ], 200);
        }

        // Connexion réussie - code existant pour la notification...
        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'token' => $token,
            'status' => 200,
        ], 200);
    }
}

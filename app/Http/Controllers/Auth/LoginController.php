<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreForgotPasswordRequest;
use App\Http\Requests\Auth\StoreLoginRequest;
use App\Http\Requests\Auth\StoreResetPasswordRequest;
use App\Mail\NewLoginNotification;
use App\Services\OtpService;
use App\Services\PasswordResetOtpService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Jenssegers\Agent\Facades\Agent;

class LoginController extends Controller
{
    protected OtpService $otpService;
    protected PasswordResetOtpService $passwordResetOtpService;

    public function __construct(OtpService $otpService, PasswordResetOtpService $passwordResetOtpService)
    {
        $this->otpService = $otpService;
        $this->passwordResetOtpService = $passwordResetOtpService;
    }

    public function login(StoreLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Support des deux formats : 'login' (nouveau) ou 'email' (ancien)
        $loginField = $validated['login'] ?? $validated['email'] ?? null;

        if (!$loginField) {
            return response()->json([
                'message' => 'Email ou numéro de téléphone requis.',
                'status' => 400,
            ], 400);
        }

        // Rechercher l'utilisateur par email ou numéro de téléphone
        $user = User::where(function($query) use ($loginField) {
            $query->where('email', $loginField)
                  ->orWhere('phone_number', $loginField);
        })->first();

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

        // Obtenir l’adresse IP de l’utilisateur
        $ipAddress = request()->ip();

        // Tenter d’obtenir la localisation via IP
        $location = 'Localisation indisponible';
        try {
            $response = Http::get("http://ip-api.com/json/{$ipAddress}?fields=country,regionName,city");
            if ($response->successful()) {
                $data     = $response->json();
                $location = "{$data['city']}, {$data['regionName']}, {$data['country']}";
            }
        } catch (\Exception $e) {
            // Logging optionnel si besoin
        }

        $userAgent = request()->header('User-Agent');

        // Information basique (chaîne brute)
        $deviceInfo = $userAgent;

        // OU utiliser un package pour une meilleure détection
        $agent      = new Agent();
        $deviceInfo = [
            'plateforme' => Agent::platform(), // Notez l'appel statique
            'navigateur' => Agent::browser(),
            'version'    => Agent::version(Agent::browser()),
            'appareil'   => Agent::isTablet() ? 'Tablette' : (Agent::isMobile() ? 'Mobile' : 'Ordinateur'),
            'robot'      => Agent::isRobot() ? Agent::robot() : false,
            'userAgent'  => $userAgent,
        ];


        // Connexion réussie
        Mail::to($user->email)->send(new NewLoginNotification($user, $ipAddress, $location, $deviceInfo));
        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'user' => $user->name,
            'token' => $token,
            'status' => 200,
        ], 200);
    }

    public function forgotPassword(StoreForgotPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé.',
                'status'  => 404,
            ], 404);
        }

        // Envoyer un OTP par email
         $this->passwordResetOtpService->send($user);

        return response()->json([
            'message' => 'OTP envoyé par email.',
            'status'  => 200,
        ], 200);
    }

    public function resetPassword(StoreResetPasswordRequest $request): JsonResponse
{
    $validated = $request->validated();

    $user = User::where('email', $validated['email'])->first();
    if (! $user) {
        return response()->json([
            'message' => 'Utilisateur non trouvé.',
            'status'  => 404,
        ], 404);
    }

    if (! $user->verifyOTP($validated['otp'])) {
        return response()->json([
            'message' => 'OTP incorrect ou expiré.',
            'status'  => 400,
        ], 400);
    }

    $user->password = Hash::make($validated['password']);
    $user->save();

    return response()->json([
        'message' => 'Mot de passe modifié.',
        'status'  => 200,
    ], 200);
}
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegisterRequest;
use App\Services\OtpService;
use App\Models\User;
use App\Models\ParentModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function register(StoreRegisterRequest $request): JsonResponse
    {

        $validated = $request->validated();

        DB::beginTransaction();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => 3,
            'phone_number' => $validated['phone_number'],
            'number_whatsapp' => $validated['number_whatsapp'],
            'password' => Hash::make($validated['password']),
        ]);


        $user->parents()->create([
            'name' => $validated['name'],
            'gender' => $validated['gender'],
            'phone_number' => $validated['phone_number'],
            'number_whatsapp' => $validated['number_whatsapp'],
            'user_id' => $user->id,
            'is_main' => true,
        ]);

        // Envoyer OTP par tous les canaux disponibles
        $results = $this->otpService->sendOtpMultiChannel($user);

        if (!$results['otp_sent']) {
            return response()->json([
                'message' => 'Erreur lors de l\'envoi de l\'OTP. Veuillez réessayer.',
                'status' => '500'
            ], 500);
        }

        $channels = [];
        if ($results['email']) $channels[] = 'email';
        if ($results['sms']) $channels[] = 'SMS';
        if ($results['whatsapp']) $channels[] = 'WhatsApp';

        DB::commit();

        return response()->json([
            'message' => 'Votre compte a été créé avec succès. Codes OTP envoyés via : ' . implode(', ', $channels),
            'user' => $user,
            'delivery_status' => $results,
            'status' => '200'
        ], 200);
    }
}

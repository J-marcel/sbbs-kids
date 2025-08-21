<?php

namespace App\Services;

use App\Mail\ResetPasswordOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class PasswordResetOtpService
{
    public function send(User $user): void
    {
        $otp = $user->generateOTP(); // Utilise la méthode du modèle User
        Mail::to($user->email)->send(new ResetPasswordOtpMail($otp, $user));
    }
}

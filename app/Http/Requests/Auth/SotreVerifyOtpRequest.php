<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SotreVerifyOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'min:6', 'max:6'],
           'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function message(){
        return [
            'otp.required' => 'Le code OTP est obligatoire.',
            'otp.string' => 'Le code OTP doit être une chaîne de caractères.',
            'otp.min' => 'Le code OTP doit avoir au moins 6 caractères.',
            'otp.max' => 'Le code OTP doit avoir au maximum 6 caractères.',
            'email.required' => 'L\'email est obligatoire.',
            'email.string' => 'L\'email doitêtre une chaîne de caractères.',
            'email.email' => 'L\'email doit avoir une forme valide.',
            'email.max' => 'L\'email doit avoir au maximum 255 caractères.',
        ];
    }
}

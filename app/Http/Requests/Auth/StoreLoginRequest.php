<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoginRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function message(){
        return [
            'email.required' => 'L\'email est obligatoire.',
            'email.string' => 'L\'email doitêtre une chaîne de caractères.',
            'email.email' => 'L\'email doit avoir une forme valide.',
            'email.max' => 'L\'email doit avoir au maximum 255 caractères.',
            'email.exists' => 'L\'email n\'existe pas.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.string' => 'Le mot de passe doitêtre une chaîne de caractères.',
            'password.min' => 'Le mot de passe doit avoir au moins 8 caractères.',
        ];
    }
}

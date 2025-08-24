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
            'login' => 'sometimes|required_without:email|string',
            'email' => 'sometimes|required_without:login|string',
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function message(){
        return [
            'login.required_without' => 'L\'email ou le numéro de téléphone est requis.',
            'login.string' => 'L\'email doitêtre une chaîne de caractères.',
            'login.max' => 'L\'email doit avoir au maximum 255 caractères.',
            'login.exists' => 'L\'email ou le numéro de téléphone n\'existe pas.',
            'email.required_without' => 'L\'email ou le numéro de téléphone est requis.',
            'email.string' => 'L\'email doitêtre une chaîne de caractères.',
            'email.max' => 'L\'email doit avoir au maximum 255 caractères.',
            'email.exists' => 'L\'email ou le numéro de téléphone n\'existe pas.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.string' => 'Le mot de passe doitêtre une chaîne de caractères.',
            'password.min' => 'Le mot de passe doit avoir au moins 8 caractères.',
        ];
    }
}

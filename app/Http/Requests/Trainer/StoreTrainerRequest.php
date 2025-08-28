<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainerRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            'email' => 'required|email|max:255|lowercase',
            'gender' => 'required|in:male,female',
            'phone_number' => 'nullable|string|max:255',
            'number_whatsapp' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'avatar.file' => 'L\'avatar doit être un fichier.',
            'avatar.mimes' => 'L\'avatar doit être un fichier de type jpeg, png ou jpg.',
            'avatar.max' => 'La taille de l\'avatar ne doit pas dépasser 2 Mo.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être une adresse email valide.',
            'email.lowercase' => 'L\'email doit être en minuscules.',
            'gender.required' => 'Le genre est obligatoire.',
            'gender.in' => 'Le genre doit être "male" ou "female".',
            'phone_number.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'phone_number.max' => 'Le numéro de téléphone ne peut pas avoir plus de 255 caractères.',
            'number_whatsapp.string' => 'Le numéro WhatsApp doit être une chaîne de caractères.',
            'number_whatsapp.max' => 'Le numéro WhatsApp ne peut pas avoir plus de 255 caractères.',
        ];
    }
}

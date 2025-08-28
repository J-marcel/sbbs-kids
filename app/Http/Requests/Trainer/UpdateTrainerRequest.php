<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainerRequest extends FormRequest
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
            'gender' => 'required|in:male,female',
            'phone_number' => 'nullable|string|max:255',
            'number_whatsapp' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'gender.required' => 'Le genre est obligatoire.',
            'gender.in' => 'Le genre doit être "male" ou "female".',
            'phone_number.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'phone_number.max' => 'Le numéro de téléphone ne peut pas avoir plus de 255 caractères.',
            'number_whatsapp.string' => 'Le numéro WhatsApp doit être une chaîne de caractères.',
            'number_whatsapp.max' => 'Le numéro WhatsApp ne peut pas avoir plus de 255 caractères.',
        ];
    }
}

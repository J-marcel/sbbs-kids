<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAvatarRequest extends FormRequest
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
            'avatars' => 'required|array|min:1|max:10',
            'avatars.*' => 'required|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Messages pour le tableau d'avatars
            'avatars.required' => 'Veuillez sélectionner au moins une image.',
            'avatars.array' => 'Le format des avatars est invalide.',
            'avatars.min' => 'Veuillez sélectionner au moins une image.',
            'avatars.max' => 'Vous ne pouvez pas télécharger plus de 10 images à la fois.',
            
            // Messages pour chaque avatar individuel
            'avatars.*.required' => 'Chaque fichier est requis.',
            'avatars.*.file' => 'Chaque avatar doit être un fichier valide.',
            'avatars.*.image' => 'Chaque fichier doit être une image.',
            'avatars.*.mimes' => 'Les images doivent être au format: jpeg, png, jpg, gif ou webp.',
            'avatars.*.max' => 'Chaque image ne doit pas dépasser 2 Mo.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'avatars' => 'avatars',
            'avatars.*' => 'avatar',
        ];
    }
}
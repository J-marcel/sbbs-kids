<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:255'],
            'number_whatsapp' => ['required', 'string', 'max:255'],
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
        ];
    }

    public function message(){
        return [
            'name.required' => 'Le nom est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom doit avoir au maximum 255 caractères.',
            'phone_number.required' => 'Le numéro de téléphone est obligatoire.',
            'phone_number.string' => 'Le numéro de téléphone doitêtre une chaîne de caractères.',
            'phone_number.max' => 'Le numéro de téléphone doit avoir au maximum 255 caractères.',
            'number_whatsapp.required' => 'Le numéro de whatsapp est obligatoire.',
            'number_whatsapp.string' => 'Le numéro de whatsapp doitêtre une chaîne de caractères.',
            'number_whatsapp.max' => 'Le numéro de whatsapp doit avoir au maximum 255 caractères.',
            'avatar.required' => 'L\'avatar est obligatoire.',
            'avatar.image' => 'L\'avatar doitêtre une image.',
            'avatar.mimes' => 'L\'avatar doitêtre une image au format JPEG, PNG, JPG ou GIF.',
            'avatar.max' => 'L\'avatar doit avoir une taille maximale de 2Mo.',
            'city.required' => 'La ville est obligatoire.',
            'city.string' => 'La ville doitêtre une chaîne de caractères.',
            'city.max' => 'La ville doit avoir au maximum 255 caractères.',
            'address.required' => 'L\'adresse est obligatoire.',
            'address.string' => 'L\'adresse doitêtre une chaîne de caractères.',
            'address.max' => 'L\'adresse doit avoir au maximum 255 caractères.',
        ];  
    }

}
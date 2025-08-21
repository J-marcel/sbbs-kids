<?php
namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegisterRequest extends FormRequest
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
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'gender'          => ['required', 'string', 'max:255'],
            'phone_number'    => ['required', 'string', 'max:255'],
            'number_whatsapp' => ['required', 'string', 'max:255'],
            'password'        => ['required', 'confirmed', 'min:8'],
            'avatar'          => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'city'            => ['nullable', 'string', 'max:255'],
            'address'         => ['nullable', 'string', 'max:255'],
        ];

    }

    public function messages(): array
    {
        return [
            'name.required'            => 'Le nom est obligatoire.',
            'name.string'              => 'Le nom doit être une chaîne de caractères.',
            'name.max'                 => 'Le nom doit avoir au maximum 255 caractères.',
            'email.required'           => 'L\'email est obligatoire.',
            'email.string'             => 'L\'email doit être une chaîne de caractères.',
            'email.email'              => 'L\'email doit avoir une forme valide.',
            'email.max'                => 'L\'email doit avoir au maximum 255 caractères.',
            'email.unique'             => 'L\'email est deja utilisé.',
            'gender.required'          => 'Le genre est obligatoire.',
            'gender.string'            => 'Le genre doit être une chaîne de caractères.',
            'gender.max'               => 'Le genre doit avoir au maximum 255 caractères.',
            'phone_number.required'    => 'Le numéro de téléphone est obligatoire.',
            'number_whatsapp.required' => 'Le numéro de whatsapp est obligatoire.',
            'password.required'        => 'Le mot de passe est obligatoire.',
            'password.confirmed'       => 'Les mots de passe ne correspondent pas.',
            'password.min'             => 'Le mot de passe doit avoir au moins 8 caractères.',
            'avatar.image'             => 'L\'avatar doitêtre une image.',
            'avatar.mimes'             => 'L\'avatar doitêtre une image au format JPEG, PNG, JPG ou GIF.',
            'avatar.max'               => 'L\'avatar doit avoir une taille maximale de 2Mo.',
            'city.string'              => 'La ville doitêtre une chaîne de caractères.',
            'city.max'                 => 'La ville doit avoir au maximum 255 caractères.',
            'address.string'           => 'L\'adresse doitêtre une chaîne de caractères.',
            'address.max'              => 'L\'adresse doit avoir au maximum 255 caractères.',
        ];
    }
}

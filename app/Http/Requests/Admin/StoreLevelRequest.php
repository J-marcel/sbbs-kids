<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLevelRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:levels,name',
            'age_group' => 'required|in:4-7,8-12,13-17|unique:levels,age_group',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est requis.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'name.unique' => 'Ce nom existe déjà.',

            'age_group.required' => 'Le groupe d\'âge est requis.',
            'age_group.in' => 'Le groupe d\'âge doit être l\'une des valeurs suivantes : 4-7, 8-12, 13-17.',
            'age_group.unique' => 'Ce groupe d\'âge existe déjà.',

            'image.required' => 'L\'image est obligatoire.',
            'image.image' => 'Le fichier doit être une image valide.',
            'image.mimes' => 'L\'image doit être au format : jpeg, png, jpg, gif ou webp.',
            'image.max' => 'L\'image ne doit pas dépasser 2 Mo.',

            'description.string' => 'La description doit être une chaîne de caractères.',
            'description.max' => 'La description ne doit pas dépasser 1000 caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'age_group' => 'groupe d\'âge',
            'image' => 'image',
            'description' => 'description',
        ];
    }
}

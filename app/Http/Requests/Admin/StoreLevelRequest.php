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
            'name' => 'required|string',
            'age_group' => 'required|in:4-7,8-12,13-17',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
        ];
    }


    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est requis.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'age_group.required' => 'Le groupe d\'âge est requis.',
            'age_group.in' => 'Le groupe d\'âge doit être dans la liste des valeurs autorisées. (4-7, 8-12, 13-17)',
            'image.required' => 'L\'image est obligatoire',
            'image.image' => 'Le fichier doit être une image valide',
            'image.mimes' => 'L\'image doit être au format jpeg, png, jpg ou gif',
            'image.max' => 'L\'image ne doit pas dépasser 2 Mo',
            'description.string' => 'La description doit être une chaîne de caractères.',
        ];
    }
}

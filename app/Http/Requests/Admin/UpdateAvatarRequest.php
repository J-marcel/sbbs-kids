<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
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
            'avatar' => 'required|file|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'avatar.required' => 'L\'avatar est requis.',
            'avatar.file' => 'L\'avatar doit être un fichier.',
            'avatar.mimes' => 'L\'avatar doit être une image (jpeg, png, jpg).',
            'avatar.max' => 'L\'avatar doit avoir une taille maximale de 2Mo.',
        ];
    }
}

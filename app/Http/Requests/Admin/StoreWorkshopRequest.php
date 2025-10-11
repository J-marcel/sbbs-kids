<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkshopRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'educational_objective' => 'required|string|max:255',
            'required_equipment' => 'required|string',
            'activity_schedule' => 'required|string',

        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Le titre est obligatoire.',
            'title.string' => 'Le titre doit être une chaîne de caractères.',
            'title.max' => 'Le titre doit contenir au maximum 255 caractères.',
            'educational_objective.required' => 'L\'objectif éducatif est obligatoire.',
            'educational_objective.string' => 'L\'objectif éducatif doit être une chaîne de caractères.',
            'required_equipment.required' => 'L\'équipement requis est obligatoire.',
            'required_equipment.string' => 'L\'équipement requis doit être une chaîne de caractères.',
            'activity_schedule.required' => 'Le calendrier des activités est obligatoire.',
            'activity_schedule.string' => 'Le calendrier des activités doit être une chaîne de caractères.',
        ];
    }
}

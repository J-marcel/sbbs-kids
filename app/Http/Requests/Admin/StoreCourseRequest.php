<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Ajustez selon votre logique d'autorisation
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
   public function rules(): array
{
    return [
        'title' => ['required', 'string', 'max:255'],
        'libelle' => ['nullable', 'string', 'max:255'],
        'objectif' => ['required', 'string'],
        'guide_for_parents' => ['required', 'string'],
        'introduction' => ['required', 'string'],
        'conclusion' => ['required', 'string'],
        'module_id' => ['required', 'integer', 'exists:modules,id'],
        'level_id' => ['required', 'integer', 'exists:levels,id'],

        // Supports
        'supports' => 'required|array|min:1',
        'supports.*.type' => 'required|in:video,support,text',
        'supports.*.libelle' => 'required|string|max:255',
        'supports.*.description' => 'nullable|string',

<<<<<<< HEAD
        // Validation conditionnelle selon le type
        'supports.*.pdf' => [
            'nullable',
            'file',
            'mimes:pdf',
            'max:10240', // 10 MB
            function ($attribute, $value, $fail) {
                $index = explode('.', $attribute)[1];
                $type = request()->input("supports.{$index}.type");
=======
              // Champs des supports (tableau)
            'supports' => 'required|array|min:1',
            'supports.*.type' => 'required|in:video,pdf,audio,text',
            'supports.*.libelle' => 'required|string|max:255',
            'supports.*.pdf' => 'nullable|file|mimes:pdf|max:2048',
            'supports.*.video' => 'nullable|url|active_url',
            'supports.*.description' => 'nullable|string',
>>>>>>> 57695c6f52b7a78282a77d41f37ae8ffdb5f1df9

                // Le PDF est requis seulement si type = 'support'
                if ($type === 'support' && !$value) {
                    $fail('Le fichier PDF est obligatoire pour un support de type "support".');
                }
            },
        ],
        'supports.*.video' => [
            'nullable',
            'url',
            function ($attribute, $value, $fail) {
                $index = explode('.', $attribute)[1];
                $type = request()->input("supports.{$index}.type");

                // La vidéo est requise seulement si type = 'video'
                if ($type === 'video' && !$value) {
                    $fail('L\'URL de la vidéo est obligatoire pour un support de type "video".');
                }
            },
        ],

        // Activities
        'activities' => 'required|array|min:1',
        'activities.*.title' => 'required|string|max:255',
        'activities.*.libelle' => 'nullable|string|max:255',
        'activities.*.description' => 'nullable|string',
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
            'title.required' => 'Le titre est obligatoire.',
            'objectif.required' => 'L\'objectif est obligatoire.',
            'guide_for_parents.required' => 'Le guide pour les parents est obligatoire.',
            'introduction.required' => 'L\'introduction est obligatoire.',
            'conclusion.required' => 'La conclusion est obligatoire.',
            'module_id.required' => 'Le module est obligatoire.',
            'module_id.exists' => 'Le module sélectionné n\'existe pas.',
            'level_id.required' => 'Le niveau est obligatoire.',
            'level_id.exists' => 'Le niveau sélectionné n\'existe pas.',

            // Messages pour les supports
            'supports.required' => 'Au moins un support est obligatoire',
            'supports.array' => 'Les supports doivent être un tableau',
            'supports.min' => 'Au moins un support est requis',
            'supports.*.libelle.required' => 'Le libellé est obligatoire pour chaque support',
            'supports.*.libelle.string' => 'Le libellé doit être une chaîne de caractères',
            'supports.*.libelle.max' => 'Le libellé doit contenir au maximum 255 caractères',
            'supports.*.pdf.file' => 'Le fichier PDF est obligatoire',
            'supports.*.pdf.mimes' => 'Le fichier PDF doit être au format PDF',
            'supports.*.pdf.max' => 'Le fichier PDF ne doit pas dépasser 2 Mo',
            'supports.*.video.url' => 'L\'URL de la vidéo est obligatoire',
            'supports.*.video.active_url' => 'L\'URL de la vidéo est invalide',
            'supports.*.description.string' => 'La description doit être une chaîne de caractères',
            'supports.*.type.required' => 'Le type est obligatoire',
<<<<<<< HEAD
            'supports.*.type.in' => 'Le type doit être une valeur parmi video, support, text',
=======
            'supports.*.type.in' => 'Le type doit être une valeur parmi video ,pdf, audio, text',
>>>>>>> 57695c6f52b7a78282a77d41f37ae8ffdb5f1df9

            // Messages pour les activités
            'activities.required' => 'Au moins une activité est obligatoire',
            'activities.array' => 'Les activités doivent être un tableau',
            'activities.min' => 'Au moins une activité est requise',
            'activities.*.title.required' => 'Le titre est obligatoire pour chaque activité',
            'activities.*.title.string' => 'Le titre doit être une chaîne de caractères',
            'activities.*.title.max' => 'Le titre doit contenir au maximum 255 caractères',
            'activities.*.libelle.string' => 'Le libellé doit être une chaîne de caractères',
            'activities.*.libelle.max' => 'Le libellé doit contenir au maximum 255 caractères',
            'activities.*.description.string' => 'La description doit être une chaîne de caractères',
        ];
    }
}

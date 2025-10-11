<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Champs du module
            'name' => 'required|string|max:255',
            'applications' => 'required|string',
            'level_id' => 'required|exists:levels,id',


            // // Champs des cours (tableau)
            // 'courses' => 'required|array|min:1',
            // 'courses.*.title' => 'required|string|max:255',
            // 'courses.*.duration' => 'required|date_format:H:i:s',
            // 'courses.*.competences' => 'required|string',
            // 'courses.*.price' => 'required|numeric|min:0',
            // 'courses.*.libelle' => 'required|string|max:255',
            // 'courses.*.video' => 'required|url|active_url',

            // // Champs des supports (tableau)
            // 'supports' => 'required|array|min:1',
            // 'supports.*.libelle' => 'required|string|max:255',
            // 'supports.*.pdf' => 'nullable|file|mimes:pdf|max:2048',
            // 'supports.*.video' => 'nullable|url|active_url',
            // 'supports.*.description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            // Messages pour le module
            'name.required' => 'Le nom du module est obligatoire',
            'name.string' => 'Le nom doit être une chaîne de caractères',
            'name.max' => 'Le nom doit contenir au maximum 255 caractères',
            'applications.required' => 'Les applications sont obligatoires',
            'level_id.required' => 'Le niveau est obligatoire',
            'level_id.exists' => 'Le niveau sélectionné n\'existe pas',


            // // Messages pour les cours
            // 'courses.required' => 'Au moins un cours est obligatoire',
            // 'courses.array' => 'Les cours doivent être un tableau',
            // 'courses.min' => 'Vous devez ajouter au moins un cours',
            // 'courses.*.title.required' => 'Le titre du cours est obligatoire',
            // 'courses.*.title.max' => 'Le titre ne doit pas dépasser 255 caractères',
            // 'courses.*.duration.required' => 'La durée est obligatoire',
            // 'courses.*.duration.date_format' => 'La durée doit être au format HH:MM:SS',
            // 'courses.*.competences.required' => 'Les compétences sont obligatoires',
            // 'courses.*.price.required' => 'Le prix est obligatoire',
            // 'courses.*.price.numeric' => 'Le prix doit être un nombre',
            // 'courses.*.price.min' => 'Le prix doit être positif',
            // 'courses.*.libelle.required' => 'Le libellé est obligatoire',
            // 'courses.*.libelle.max' => 'Le libellé ne doit pas dépasser 255 caractères',
            // 'courses.*.video.required' => 'L\'URL de la vidéo est obligatoire',
            // 'courses.*.video.url' => 'L\'URL de la vidéo n\'est pas valide',
            // 'courses.*.video.active_url' => 'L\'URL de la vidéo n\'est pas accessible',

            // // Messages pour les supports
            // 'supports.required' => 'Au moins un support est obligatoire',
            // 'supports.array' => 'Les supports doivent être un tableau',
            // 'supports.min' => 'Vous devez ajouter au moins un support',
            // 'supports.*.libelle.required' => 'Le libellé du support est obligatoire',
            // 'supports.*.libelle.max' => 'Le libellé du support ne doit pas dépasser 255 caractères',
            // 'supports.*.pdf.file' => 'Le fichier PDF doit être un fichier valide',
            // 'supports.*.pdf.mimes' => 'Le fichier PDF doit être au format PDF',
            // 'supports.*.pdf.max' => 'Le fichier PDF ne doit pas dépasser 2 Mo',
            // 'supports.*.video.url' => 'L\'URL de la vidéo n\'est pas valide',
            // 'supports.*.video.active_url' => 'L\'URL de la vidéo n\'est pas accessible',
        ];
    }
}

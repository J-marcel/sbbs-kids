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
            'admin_id' => 'required|exists:admins,id',
            'support_id' => 'required|exists:supports,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',

            // Champs des cours (tableau)
            'courses' => 'required|array|min:1',
            'courses.*.title' => 'required|string|max:255',
            'courses.*.duration' => 'required|date_format:H:i:s',
            'courses.*.competences' => 'required|string',
            'courses.*.price' => 'required|numeric|min:0',
            'courses.*.libelle' => 'required|string|max:255',
            'courses.*.video' => 'required|url|active_url', // URL valide et accessible
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
            'admin_id.required' => 'L\'administrateur est obligatoire',
            'admin_id.exists' => 'L\'administrateur sélectionné n\'existe pas',
            'support_id.required' => 'Le support est obligatoire',
            'support_id.exists' => 'Le support sélectionné n\'existe pas',
            'image.required' => 'L\'image est obligatoire',
            'image.image' => 'Le fichier doit être une image valide',
            'image.mimes' => 'L\'image doit être au format jpeg, png, jpg ou gif',
            'image.max' => 'L\'image ne doit pas dépasser 2 Mo',

            // Messages pour les cours
            'courses.required' => 'Au moins un cours est obligatoire',
            'courses.array' => 'Les cours doivent être un tableau',
            'courses.min' => 'Vous devez ajouter au moins un cours',
            'courses.*.title.required' => 'Le titre du cours est obligatoire',
            'courses.*.title.max' => 'Le titre ne doit pas dépasser 255 caractères',
            'courses.*.duration.required' => 'La durée est obligatoire',
            'courses.*.duration.date_format' => 'La durée doit être au format HH:MM:SS',
            'courses.*.competences.required' => 'Les compétences sont obligatoires',
            'courses.*.price.required' => 'Le prix est obligatoire',
            'courses.*.price.numeric' => 'Le prix doit être un nombre',
            'courses.*.price.min' => 'Le prix doit être positif',
            'courses.*.libelle.required' => 'Le libellé est obligatoire',
            'courses.*.libelle.max' => 'Le libellé ne doit pas dépasser 255 caractères',
            'courses.*.video.required' => 'L\'URL de la vidéo est obligatoire',
            'courses.*.video.url' => 'L\'URL de la vidéo n\'est pas valide',
            'courses.*.video.active_url' => 'L\'URL de la vidéo n\'est pas accessible',
        ];
    }
}

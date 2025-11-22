<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Traits\FileHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class ParentController extends Controller
{
    use FileHandler;

    /**
     * Afficher la liste des étudiants avec informations d'abonnement
     */
    public function index(): JsonResponse
    {
        // ✅ Charger les relations pour les infos d'abonnement
        $students = Student::with([
            'avatar',
            'subscriptions' => function ($query) {
                $query->where('status', 'active')
                    ->where('end_date', '>', now())
                    ->with('plan');
            }
        ])
            ->latest()
            ->get();

        // ✅ Statistiques globales
        $statistics = [
            'total' => $students->count(),
            'with_active_subscription' => $students->filter(fn($s) => $s->has_active_subscription)->count(),
            'without_subscription' => $students->filter(fn($s) => !$s->has_active_subscription)->count(),
            'expiring_soon' => $students->filter(fn($s) => $s->subscriptionExpiringSoon())->count(),
        ];

        return response()->json([
            'students' => $students,
            'statistics' => $statistics,
            'status' => 200,
        ], 200);
    }

    /**
     * Créer un nouveau student pour le parent connecté
     */
    public function storeStudent(Request $request): JsonResponse
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'age_group' => ['required', 'in:4-7,8-12,13-17'],
            'age' => ['required', 'integer', 'min:4', 'max:17'],
            'pin_code' => ['required', 'string', 'min:4', 'max:4'],
            'avatar_id' => ['nullable', 'exists:avatars,id'],
        ], [
            'name.required' => 'Le nom de l\'étudiant est requis.',
            'name.string' => 'Le nom de l\'étudiant doit être une chaîne de caractères.',
            'name.max' => 'Le nom de l\'étudiant ne doit pas dépasser 255 caractères.',

            'gender.required' => 'Le genre de l\'étudiant est requis.',
            'gender.in' => 'Le genre de l\'étudiant doit être "male" ou "female".',

            'age_group.required' => 'La tranche d\'âge de l\'étudiant est requise.',
            'age_group.in' => 'La tranche d\'âge doit être "4-7", "8-12", "13-17".',

            'age.required' => 'L\'âge de l\'étudiant est requis.',
            'age.integer' => 'L\'âge doit être un entier.',
            'age.min' => 'L\'âge doit être au moins 4.',
            'age.max' => 'L\'âge doit être au maximum 17.',

            'pin_code.required' => 'Le code PIN de l\'étudiant est requis.',
            'pin_code.string' => 'Le code PIN doit être une chaîne de caractères.',
            'pin_code.min' => 'Le code PIN doit comporter exactement 4 caractères.',
            'pin_code.max' => 'Le code PIN doit comporter exactement 4 caractères.',

            'avatar_id.exists' => 'L\'avatar spécifié n\'existe pas.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Vérifier que l'âge correspond à la tranche d'âge
        $ageGroupRanges = [
            '4-7' => ['min' => 4, 'max' => 7],
            '8-12' => ['min' => 8, 'max' => 12],
            '13-17' => ['min' => 13, 'max' => 17],
        ];

        $selectedAgeGroup = $request->age_group;
        $age = $request->age;

        if ($age < $ageGroupRanges[$selectedAgeGroup]['min'] || $age > $ageGroupRanges[$selectedAgeGroup]['max']) {
            return response()->json([
                'message' => "L'âge $age ne correspond pas à la tranche d'âge $selectedAgeGroup",
                'errors' => [
                    'age' => ["L'âge doit être compris entre {$ageGroupRanges[$selectedAgeGroup]['min']} et {$ageGroupRanges[$selectedAgeGroup]['max']} pour la tranche d'âge $selectedAgeGroup"]
                ],
                'status' => 422,
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Récupérer le parent principal connecté
            $mainParent = ParentModel::where('user_id', $request->user()->id)
                ->where('is_main', true)
                ->first();

            if (!$mainParent) {
                return response()->json([
                    'message' => 'Parent principal non trouvé',
                    'status' => 404,
                ], 404);
            }

            // Création de l'étudiant
            $student = Student::create([
                'parent_model_id' => $mainParent->id,
                'name' => $request->name,
                'gender' => $request->gender,
                'age_group' => $request->age_group,
                'age' => $request->age,
                'avatar_id' => $request->avatar_id,
                'pin_code' => Hash::make($request->pin_code),
                'role_id' => 4,
            ]);

            // ✅ Charger les relations pour les infos d'abonnement
            $student->load([
                'avatar',
                'subscriptions' => function ($query) {
                    $query->where('status', 'active')
                        ->where('end_date', '>', now())
                        ->with('plan');
                }
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Étudiant créé avec succès',
                'student' => $student,
                'subscription_info' => [
                    'has_active_subscription' => $student->has_active_subscription,
                    'needs_subscription' => !$student->has_active_subscription,
                    'recommended_plans_for_age_group' => $student->age_group,
                ],
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création de l\'étudiant',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Mettre à jour un étudiant
     */
    public function updateStudent(Request $request, $studentId)
    {
        // Récupérer le parent principal
        $mainParent = ParentModel::where('user_id', $request->user()->id)
            ->where('is_main', true)
            ->first();

        if (!$mainParent) {
            return response()->json([
                'message' => 'Parent principal non trouvé',
                'status' => 404,
            ], 404);
        }

        // Récupérer l'étudiant
        $student = Student::where('id', $studentId)
            ->where('parent_model_id', $mainParent->id)
            ->first();

        if (!$student) {
            return response()->json([
                'message' => 'Étudiant non trouvé',
                'status' => 404,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'gender' => 'sometimes|required|in:male,female',
            'age_group' => 'sometimes|required|in:4-7,8-12,13-17',
            'age' => 'sometimes|required|integer|min:4|max:17',
            'avatar_id' => 'sometimes|required|exists:avatars,id',
            'pin_code' => 'sometimes|required|string|min:4|max:4',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Vérifier que l'âge correspond à la tranche d'âge si les deux sont modifiés
        if ($request->has('age_group') || $request->has('age')) {
            $ageGroupRanges = [
                '4-7' => ['min' => 4, 'max' => 7],
                '8-12' => ['min' => 8, 'max' => 12],
                '13-17' => ['min' => 13, 'max' => 17],
            ];

            $selectedAgeGroup = $request->age_group ?? $student->age_group;
            $age = $request->age ?? $student->age;

            if ($age < $ageGroupRanges[$selectedAgeGroup]['min'] || $age > $ageGroupRanges[$selectedAgeGroup]['max']) {
                return response()->json([
                    'message' => "L'âge $age ne correspond pas à la tranche d'âge $selectedAgeGroup",
                    'errors' => [
                        'age' => ["L'âge doit être compris entre {$ageGroupRanges[$selectedAgeGroup]['min']} et {$ageGroupRanges[$selectedAgeGroup]['max']} pour la tranche d'âge $selectedAgeGroup"]
                    ],
                    'status' => 422,
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            // Mise à jour des données de base
            $student->fill($request->only(['name', 'gender', 'age_group', 'age', 'avatar_id']));

            // Mise à jour du PIN si fourni
            if ($request->has('pin_code')) {
                $student->pin_code = Hash::make($request->pin_code);
            }

            $student->save();

            // ✅ Charger les relations pour les infos d'abonnement
            $student->load([
                'avatar',
                'subscriptions' => function ($query) {
                    $query->where('status', 'active')
                        ->where('end_date', '>', now())
                        ->with('plan');
                }
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Étudiant mis à jour avec succès',
                'student' => $student,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Afficher les étudiants du parent connecté avec infos d'abonnement
     */
    public function getMyStudents(Request $request)
    {
        $mainParent = ParentModel::where('user_id', $request->user()->id)
            ->where('is_main', true)
            ->first();

        if (!$mainParent) {
            return response()->json([
                'message' => 'Parent principal non trouvé',
                'status' => 404,
            ], 404);
        }

        // ✅ Charger les students avec leurs abonnements actifs
        $students = Student::where('parent_model_id', $mainParent->id)
            ->with([
                'avatar',
                'subscriptions' => function ($query) {
                    $query->where('status', 'active')
                        ->where('end_date', '>', now())
                        ->with('plan');
                }
            ])
            ->get();

        // ✅ Grouper les students par statut d'abonnement
        $studentsWithSubscription = $students->filter(fn($s) => $s->has_active_subscription);
        $studentsWithoutSubscription = $students->filter(fn($s) => !$s->has_active_subscription);
        $studentsExpiringSoon = $students->filter(fn($s) => $s->subscriptionExpiringSoon());

        // ✅ Statistiques détaillées
        $statistics = [
            'total_students' => $students->count(),
            'with_active_subscription' => $studentsWithSubscription->count(),
            'without_subscription' => $studentsWithoutSubscription->count(),
            'expiring_soon' => $studentsExpiringSoon->count(),
            'by_age_group' => [
                '4-7' => [
                    'total' => $students->where('age_group', '4-7')->count(),
                    'with_subscription' => $studentsWithSubscription->where('age_group', '4-7')->count(),
                ],
                '8-12' => [
                    'total' => $students->where('age_group', '8-12')->count(),
                    'with_subscription' => $studentsWithSubscription->where('age_group', '8-12')->count(),
                ],
                '13-17' => [
                    'total' => $students->where('age_group', '13-17')->count(),
                    'with_subscription' => $studentsWithSubscription->where('age_group', '13-17')->count(),
                ],
            ],
        ];

        return response()->json([
            'parent' => [
                'id' => $mainParent->id,
                'name' => $mainParent->name,
                'email' => $mainParent->email,
            ],
            'students' => $students,
            'grouped_students' => [
                'with_subscription' => $studentsWithSubscription->values(),
                'without_subscription' => $studentsWithoutSubscription->values(),
                'expiring_soon' => $studentsExpiringSoon->values(),
            ],
            'statistics' => $statistics,
            'status' => 200,
        ], 200);
    }

    /**
     * Switch vers le profil d'un étudiant avec PIN
     */
    public function switchToStudentProfile(Request $request, $studentId)
    {
        // Vérifier que l'utilisateur est connecté en tant que parent
        $mainParent = ParentModel::where('user_id', $request->user()->id)
            ->where('is_main', true)
            ->first();

        if (!$mainParent) {
            return response()->json([
                'message' => 'Accès refusé. Parent principal requis.',
                'status' => 403,
            ], 403);
        }

        // Récupérer l'étudiant qui appartient à ce parent
        $student = Student::where('id', $studentId)
            ->where('parent_model_id', $mainParent->id)
            ->with([
                'avatar',
                'role:id,name',
                'subscriptions' => function ($query) {
                    $query->where('status', 'active')
                        ->where('end_date', '>', now())
                        ->with('plan');
                }
            ])
            ->first();

        if (!$student) {
            return response()->json([
                'message' => 'Étudiant non trouvé ou non autorisé',
                'status' => 404,
            ], 404);
        }

        // ✅ Vérifier si le student a un abonnement actif
        if (!$student->has_active_subscription) {
            return response()->json([
                'message' => 'Cet étudiant n\'a pas d\'abonnement actif',
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'age_group' => $student->age_group,
                ],
                'subscription_status' => $student->subscription_status,
                'action_required' => 'Veuillez souscrire à un abonnement pour accéder au profil de cet étudiant',
                'status' => 403,
            ], 403);
        }

        // Validation du PIN
        $validator = Validator::make($request->all(), [
            'pin_code' => 'required|string|min:4|max:4',
        ], [
            'pin_code.required' => 'Le code PIN est requis.',
            'pin_code.string' => 'Le code PIN doit être une chaîne de caractères.',
            'pin_code.min' => 'Le code PIN doit comporter 4 caractères.',
            'pin_code.max' => 'Le code PIN doit comporter 4 caractères.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Vérifier le PIN
        if (!Hash::check($request->pin_code, $student->pin_code)) {
            return response()->json([
                'message' => 'Code PIN incorrect',
                'status' => 401,
            ], 401);
        }

        // Créer un token spécifique pour cet étudiant
        $token = $request->user()->createToken('student_token', ['student:' . $studentId])->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie au profil étudiant',
            'token_type' => 'Bearer',
            'student' => $student,
            'parent' => [
                'id' => $mainParent->id,
                'name' => $mainParent->name,
            ],
            'access_token' => $token,
            'status' => 200,
        ], 200);
    }

    /**
     * Modifier le PIN d'un étudiant
     */
    public function updateStudentPin(Request $request, $studentId)
    {
        // Récupérer le parent principal
        $mainParent = ParentModel::where('user_id', $request->user()->id)
            ->where('is_main', true)
            ->first();

        if (!$mainParent) {
            return response()->json([
                'message' => 'Parent principal non trouvé',
                'status' => 404,
            ], 404);
        }

        // Récupérer l'étudiant
        $student = Student::where('id', $studentId)
            ->where('parent_model_id', $mainParent->id)
            ->first();

        if (!$student) {
            return response()->json([
                'message' => 'Étudiant non trouvé',
                'status' => 404,
            ], 404);
        }

        // Validation des données
        $validator = Validator::make($request->all(), [
            'current_pin' => 'required|string|min:4|max:4',
            'new_pin' => 'required|string|min:4|max:4',
            'new_pin_confirmation' => 'required|string|same:new_pin',
        ], [
            'current_pin.required' => 'L\'ancien code PIN est requis.',
            'current_pin.string' => 'L\'ancien code PIN doit être une chaîne de caractères.',
            'current_pin.min' => 'L\'ancien code PIN doit comporter 4 caractères.',
            'current_pin.max' => 'L\'ancien code PIN doit comporter 4 caractères.',

            'new_pin.required' => 'Le nouveau code PIN est requis.',
            'new_pin.string' => 'Le nouveau code PIN doit être une chaîne de caractères.',
            'new_pin.min' => 'Le nouveau code PIN doit comporter 4 caractères.',
            'new_pin.max' => 'Le nouveau code PIN doit comporter 4 caractères.',

            'new_pin_confirmation.required' => 'La confirmation du nouveau PIN est requise.',
            'new_pin_confirmation.same' => 'La confirmation du PIN ne correspond pas.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Vérifier l'ancien PIN
        if (!Hash::check($request->current_pin, $student->pin_code)) {
            return response()->json([
                'message' => 'L\'ancien code PIN est incorrect',
                'status' => 401,
            ], 401);
        }

        // Vérifier que le nouveau PIN est différent de l'ancien
        if (Hash::check($request->new_pin, $student->pin_code)) {
            return response()->json([
                'message' => 'Le nouveau code PIN doit être différent de l\'ancien',
                'status' => 422,
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Mettre à jour le PIN
            $student->pin_code = Hash::make($request->new_pin);
            $student->save();

            DB::commit();

            return response()->json([
                'message' => 'Code PIN modifié avec succès',
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'updated_at' => $student->updated_at,
                ],
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la modification du code PIN',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Réinitialiser le PIN d'un étudiant (par le parent)
     */
    public function resetStudentPin(Request $request, $studentId)
    {
        // Récupérer le parent principal
        $mainParent = ParentModel::where('user_id', $request->user()->id)
            ->where('is_main', true)
            ->first();

        if (!$mainParent) {
            return response()->json([
                'message' => 'Parent principal non trouvé',
                'status' => 404,
            ], 404);
        }

        // Récupérer l'étudiant
        $student = Student::where('id', $studentId)
            ->where('parent_model_id', $mainParent->id)
            ->first();

        if (!$student) {
            return response()->json([
                'message' => 'Étudiant non trouvé',
                'status' => 404,
            ], 404);
        }

        // Validation des données
        $validator = Validator::make($request->all(), [
            'new_pin' => 'required|string|min:4|max:4',
            'new_pin_confirmation' => 'required|string|same:new_pin',
            'parent_confirmation' => 'required|boolean|accepted', // Le parent doit confirmer l'action
        ], [
            'new_pin.required' => 'Le nouveau code PIN est requis.',
            'new_pin.string' => 'Le nouveau code PIN doit être une chaîne de caractères.',
            'new_pin.min' => 'Le nouveau code PIN doit comporter 4 caractères.',
            'new_pin.max' => 'Le nouveau code PIN doit comporter 4 caractères.',

            'new_pin_confirmation.required' => 'La confirmation du nouveau PIN est requise.',
            'new_pin_confirmation.same' => 'La confirmation du PIN ne correspond pas.',

            'parent_confirmation.required' => 'La confirmation du parent est requise.',
            'parent_confirmation.accepted' => 'Vous devez confirmer cette action.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();

        try {
            // Réinitialiser le PIN
            $student->pin_code = Hash::make($request->new_pin);
            $student->save();

            DB::commit();

            return response()->json([
                'message' => 'Code PIN réinitialisé avec succès',
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'updated_at' => $student->updated_at,
                ],
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la réinitialisation du code PIN',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Supprimer un étudiant
     */
    public function destroyStudent($studentId, Request $request)
    {
        // Récupérer le parent principal
        $mainParent = ParentModel::where('user_id', $request->user()->id)
            ->where('is_main', true)
            ->first();

        if (!$mainParent) {
            return response()->json([
                'message' => 'Parent principal non trouvé',
                'status' => 404,
            ], 404);
        }

        // Récupérer et supprimer l'étudiant
        $student = Student::where('id', $studentId)
            ->where('parent_model_id', $mainParent->id)
            ->first();

        if (!$student) {
            return response()->json([
                'message' => 'Étudiant non trouvé',
                'status' => 404,
            ], 404);
        }

        DB::beginTransaction();

        try {
            // Supprimer l'avatar s'il existe
            if ($student->avatar) {
                $this->deleteFile($student->avatar);
            }

            $student->delete();

            DB::commit();

            return response()->json([
                'message' => 'Étudiant supprimé avec succès',
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Déconnecter du profil étudiant et retourner au profil parent
     */
    public function logoutFromStudentProfile(Request $request)
    {
        // Révoquer le token actuel
        $request->user()->currentAccessToken()->delete();

        // Créer un nouveau token parent
        $token = $request->user()->createToken('parent_token')->plainTextToken;

        return response()->json([
            'message' => 'Déconnexion du profil étudiant réussie',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'status' => 200,
        ], 200);
    }
}

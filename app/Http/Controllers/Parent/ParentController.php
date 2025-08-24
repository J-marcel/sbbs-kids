<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Traits\FileHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;



class ParentController extends Controller
{
    use FileHandler;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $parents = ParentModel::where('user_id', auth()->user()->id)
        ->with('student')
        ->get();
        return response()->json([
            'parents' => $parents,
            'status'   => 200,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeStudent(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [

            'name'      => ['required', 'string', 'max:255'],
            'avatar'    => ['nullable', 'file', 'mimes:jpeg,png,jpg', 'max:2048'],
            'gender'    => ['required', 'in:male,female'],
            'age_group' => ['required', 'in:4-6,7-10,11-15,16-18'],
            'pin_code'  => ['required', 'string', 'min:4', 'max:4'],
        ], [
            'name.required' => 'Le nom du parent est requis.',
            'name.string' => 'Le nom du parent doit être une chaîne de caractères.',
            'name.max' => 'Le nom du parent ne doit pas dépasser 255 caractères.',

            'avatar.file' => 'L\'avatar doit être un fichier.',
            'avatar.mimes' => 'L\'avatar doit être un fichier de type jpeg, png ou jpg.',
            'avatar.max' => 'La taille de l\'avatar ne doit pas dépasser 2 Mo.',

            'gender.required' => 'Le genre du parent est requis.',
            'gender.in' => 'Le genre du parent doit être "male" ou "female".',

            'age_group.required' => 'L\'âge du parent est requis.',
            'age_group.in' => 'L\'âge du parent doit être "4-6", "7-10", "11-15" ou "16-18".',

            'pin_code.required' => 'Le code PIN du parent est requis.',
            'pin_code.string' => 'Le code PIN du parent doit être une chaîne de caractères.',
            'pin_code.min' => 'Le code PIN du parent doit comporter exactement 4 caractères.',
            'pin_code.max' => 'Le code PIN du parent doit comporter exactement 4 caractères.',

        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();

        try {
            // Gestion de l'avatar
            $avatar = null;
            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar')->store('avatars', 'public');
            }

            // Création du parent
            $parent = ParentModel::create([
                'user_id'  => $request->user()->id,
                'name'     => $request->name,
                'avatar'   => $avatar,
                'is_main'  => false,
                'is_child' => true,
            ]);

            // Si le pin_code existe, on crée un étudiant lié
            Student::create([
                'parent_model_id' => $parent->id,
                'name'            => $request->name,
                'gender'          => $request->gender,
                'age_group'       => $request->age_group,
                'pin_code'        => Hash::make($request->pin_code),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Student créé avec succès',
                'parent'  => $parent->load('student'),
                'status'  => 200,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création du student',
                'error'   => $e->getMessage(),
                'status'  => 500,
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function switchProfile(Request $request, $parentmodelId)
    {
        $parent = ParentModel::where('user_id', $request->user()->id)
            ->where('id', $parentmodelId)
            ->firstOrFail();

        // Le PIN est toujours requis maintenant
        $validator = Validator::make($request->all(), [
            'pin_code' => 'required|string|min:4|max:4',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Vérifier le PIN
        if (!Hash::check($request->pin_code, $parent->student->pin_code)) {
            return response()->json([
                'message' => 'Invalid PIN code'
            ], 401);
        }

        // Créer un token spécifique au profil
        $token = $request->user()->createToken('parent_token', ['parent:' . $parentmodelId])->plainTextToken;

        return response()->json([
            'token_type' => 'Bearer',
            'parent' => $parent,
            'access_token' => $token,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ParentModel $parentModel)
    {
        $parent = ParentModel::where('user_id', $request->user()->id)
        ->where('id', $parentModel->id)
        ->where('is_main', false)
        ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'avatar' => 'nullable|string',
            'gender' => 'required|in:male,female',
            'age_group' => 'required|in:4-6,7-10,11-15,16-18',
            'pin_code' => 'required|string|min:4|max:4', // Changé en required
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $parent->update($request->only(['name', 'avatar','gender','age_group']));

        // Mise à jour du PIN (toujours présent car obligatoire)
        if ($parent->student) {
            $parent->student->update([
                'pin_code' => Hash::make($request->pin_code),
            ]);
        } else {
            Student::create([
                'parent_model_id' => $parent->id,
                'pin_code' => Hash::make($request->pin_code),
            ]);
        }

        // Le profil est toujours un profil enfant avec PIN
        $parent->is_child = true;
        $parent->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'parent' => $parent->load('student')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ParentModel $parentModel)
    {
        DB::beginTransaction();

        $parent = ParentModel::where('user_id', $parentModel->user_id)
        ->where('id', $parentModel->id)
        ->where('is_main', false)
        ->firstOrFail();

        $parent->delete();

        DB::commit();

        return response()->json([
            'message' => 'Student supprimé avec succès',
            'status'  => 200,
        ], 200);
    }
}

<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\student;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentrequest;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ParentModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $students = student::latest()->get();
        return response()->json([
            'students' => $students,
            'status'   => 200,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request)
    {
        // Vérifier si l'email existe déjà
        $verifEmail = User::where('email', $request->email)->first();
        if ($verifEmail) {
            return response()->json([
                'message' => 'Email deja utilisé',
                'status'  => 400,
            ], 400);
        }

        $validated = $request->validated();

        DB::beginTransaction();

        $currentUser = auth()->user();
        $verifParent = ParentModel::where('user_id', $currentUser->id)->first();

        if (!$verifParent) {
            return response()->json([
                'message' => 'Vous devez être connecté en tant que parent',
                'status'  => 403,
            ], 403);
        }

        // Créer l'utilisateur (avec un mot de passe aléatoire ou autre)
        $randomPassword = str()->random(8);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'number_whatsapp' => $validated['number_whatsapp'],
            'role_id' => 4,
            'is_otp_verified' => true,
            'email_verified_at' => now(),
            'otp' => null,
            'otp_expires_at' => null,
            'password' => Hash::make($randomPassword),
        ]);

        // Créer l'étudiant en liant avec l'utilisateur
        $studentData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'gender' => $validated['gender'],
            'age_group' => $validated['age_group'],
            'phone_number' => $validated['phone_number'],
            'number_whatsapp' => $validated['number_whatsapp'],
            'user_id' => $user->id, // Ajoutez cette ligne
            'parent_model_id' => $verifParent->id,
        ];

        $student = Student::create($studentData);

        // Si vous souhaitez associer explicitement
        $student->user()->associate($user);
        $student->save();

        DB::commit();

        return response()->json([
            'message' => 'Étudiant créé avec succès',
            'student' => $student,
            'status'  => 200,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(student $student)
    {
        return response()->json([
            'message' => 'Étudiant récupéré avec succès',
            'student' => $student,
            'status'  => 200,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentrequest $request, student $student)
    {
        $validated = $request->validated();
        DB::beginTransaction();

        $student->update($validated);

        DB::commit();

        return response()->json([
            'message' => 'Étudiant mis à jour avec succès',
            'student' => $student,
            'status'  => 200,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(student $student)
    {
        DB::beginTransaction();

        $student->delete();

        DB::commit();

        return response()->json([
            'message' => 'Étudiant supprimé avec succès',
            'status'  => 200,
        ], 200);
    }
}

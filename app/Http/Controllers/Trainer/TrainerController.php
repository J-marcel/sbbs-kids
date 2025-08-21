<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trainer\StoreTrainerRequest;
use App\Http\Requests\Trainer\UpdateTrainerRequest;
use App\Models\Admin;
use App\Models\Trainer;
use App\Models\User;
use App\Services\TrainerWelcomeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TrainerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $trainers = Trainer::latest()->get();
        return response()->json([
            'trainers' => $trainers,
            'status'   => 200,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTrainerRequest $request)
    {
        //  Vérifier si l'email existe déjà
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
        $verifAdmin = Admin::where('user_id', $currentUser->id)->first();

        if (!$verifAdmin) {
            return response()->json([
                'message' => 'Vous devez être connecté en tant que admin',
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
            'role_id' => 2,
            'is_otp_verified' => true,
            'email_verified_at' => now(),
            'otp' => null,
            'otp_expires_at' => null,
            'password' => Hash::make($randomPassword),
        ]);

        // Créer l'étudiant en liant avec l'utilisateur
        $trainerData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'gender' => $validated['gender'],
            'phone_number' => $validated['phone_number'],
            'number_whatsapp' => $validated['number_whatsapp'],
            'user_id' => $user->id, // Ajoutez cette ligne
            'admin_id' => $verifAdmin->id,
        ];

        $trainer = Trainer::create($trainerData);

        // Si vous souhaitez associer explicitement
        $trainer->user()->associate($user);
        $trainer->save();

        DB::commit();

        $trainerWelcomeService = app(TrainerWelcomeService::class);
        $trainerWelcomeService->sendWelcomeEmail($trainer, $user);

        return response()->json([
            'message' => 'Enseignant créé avec succès',
            'trainer' => $trainer,
            'status'  => 200,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Trainer $trainer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTrainerRequest $request, Trainer $trainer)
    {
        $validated = $request->validated();
        DB::beginTransaction();

        $trainer->update($validated);

        DB::commit();

        return response()->json([
            'message' => 'Enseignant mis à jour avec succès',
            'trainer' => $trainer,
            'status'  => 200,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trainer $trainer)
    {
        DB::beginTransaction();

        $trainer->delete();

        DB::commit();

        return response()->json([
            'message' => 'Enseignant supprimé avec succès',
            'status'  => 200,
        ], 200);
    }
}

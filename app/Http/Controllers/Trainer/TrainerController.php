<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trainer\StoreTrainerRequest;
use App\Http\Requests\Trainer\UpdateTrainerRequest;
use App\Models\Admin;
use App\Models\Trainer;
use App\Models\User;
use App\Services\TrainerWelcomeService;
use App\Traits\FileHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TrainerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use FileHandler;
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
                'message' => 'Email deja utilisé',
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

        if ($request->hasFile('avatar')) {
            $avatar = $this->uploadFile($request->file('avatar'), 'avatars');
            $validated['avatar'] = $avatar;
        }

        // Créer l'utilisateur (avec un mot de passe aléatoire)
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
            'user_id' => $user->id,
            'admin_id' => $verifAdmin->id,
            'avatar' => $validated['avatar'],
        ];

        $trainer = Trainer::create($trainerData);

        // Si vous souhaitez associer explicitement
        $trainer->user()->associate($user);
        $trainer->save();

        DB::commit();

        // Envoyer l'email avec le mot de passe généré
        $trainerWelcomeService = app(TrainerWelcomeService::class);
        $trainerWelcomeService->sendWelcomeEmail($trainer, $user, $randomPassword);

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
        try {
            $userUpdateData = [];
            $trainerUpdateData = [];

            $userFields = ['name', 'phone_number', 'number_whatsapp'];
            $trainerFields = ['name', 'gender', 'phone_number', 'number_whatsapp'];

            foreach ($validated as $key => $value) {
                if (in_array($key, $userFields)) {
                    $userUpdateData[$key] = $value;
                }
                if (in_array($key, $trainerFields)) {
                    $trainerUpdateData[$key] = $value;
                }
            }

            if (!empty($userUpdateData) && $trainer->user_id) {
                $user = User::find($trainer->user_id);
                if ($user) {
                    $user->update($userUpdateData);
                }
            }

            if ($request->hasFile('avatar')) {
                $avatar = $this->uploadFile($request->file('avatar'), 'avatars');
                $trainer->avatar = $avatar;
            }

            if (!empty($trainerUpdateData)) {
                $trainer->update($trainerUpdateData);
            }

            DB::commit();

            $trainer->load('user');

            return response()->json([
                'message' => 'Enseignant mis à jour avec succès',
                'trainer' => $trainer,
                'status'  => 200,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de l\'enseignant',
                'status'  => 500,
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trainer $trainer)
    {
        DB::beginTransaction();
        try {
            if ($trainer->user_id) {
                $user = User::find($trainer->user_id);
                if ($user) {
                    $user->delete();
                }
            }
            $trainer->delete();
            DB::commit();
            return response()->json([
                'message' => 'Enseignant supprimé avec succès',
                'status'  => 200,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la suppression de l\'enseignant',
                'status'  => 500,
            ], 500);
        }
    }
}

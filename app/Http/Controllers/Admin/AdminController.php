<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $admins = Admin::latest()->get();
        return response()->json([
            'admins' => $admins,
            'status'   => 200,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdminRequest $request)
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
            'role_id' => 1,
            'is_otp_verified' => true,
            'email_verified_at' => now(),
            'otp' => null,
            'otp_expires_at' => null,
            'password' => Hash::make($randomPassword),
        ]);

        // Créer l'étudiant en liant avec l'utilisateur
        $adminData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'gender' => $validated['gender'],
            'phone_number' => $validated['phone_number'],
            'number_whatsapp' => $validated['number_whatsapp'],
            'user_id' => $user->id, // Ajoutez cette ligne
            'admin_id' => $verifAdmin->id,
        ];

        $admin = Admin::create($adminData);

        // Si vous souhaitez associer explicitement
        $admin->user()->associate($user);
        $admin->save();

        DB::commit();

        return response()->json([
            'message' => 'Admin créé avec succès',
            'admin' => $admin,
            'status'  => 200,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdminRequest $request, Admin $admin)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $userUpdateData = [];
            $adminUpdateData = [];


            $userFields = ['name', 'phone_number', 'number_whatsapp'];
            $adminFields = ['name', 'gender', 'phone_number', 'number_whatsapp'];

            foreach ($validated as $key => $value) {
                if (in_array($key, $userFields)) {
                    $userUpdateData[$key] = $value;
                }
                if (in_array($key, $adminFields)) {
                    $adminUpdateData[$key] = $value;
                }
            }

            if (!empty($userUpdateData) && $admin->user_id) {
                $user = User::find($admin->user_id);
                if ($user) {
                    $user->update($userUpdateData);
                }
            }

            if (!empty($adminUpdateData)) {
                $admin->update($adminUpdateData);
            }

            DB::commit();

            $admin->load('user');

            return response()->json([
                'message' => 'Admin mis à jour avec succès',
                'admin' => $admin,
                'status'  => 200,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage(),
                'status'  => 500,
            ], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        // Vérifier si c'est l'admin avec ID 1 (protection)
        if ($admin->id == 1) {
            return response()->json([
                'message' => 'Impossible de supprimer cet administrateur',
                'status'  => 403,
            ], 403);
        }

        // Vérifier si l'utilisateur associé a l'ID 1 (protection)
        if ($admin->user_id == 1) {
            return response()->json([
                'message' => 'Impossible de supprimer cet administrateur',
                'status'  => 403,
            ], 403);
        }

        DB::beginTransaction();

        try {
            // Supprimer l'utilisateur associé si nécessaire
            if ($admin->user_id) {
                $user = User::find($admin->user_id);
                if ($user) {
                    $user->delete();
                }
            }

            // Supprimer l'admin
            $admin->delete();

            DB::commit();

            return response()->json([
                'message' => 'Admin supprimé avec succès',
                'status'  => 200,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage(),
                'status'  => 500,
            ], 500);
        }
    }
}


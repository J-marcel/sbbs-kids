<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreUpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\ParentModel;
use App\Models\User;
use App\Traits\FileHandler;
use Illuminate\Container\Attributes\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use FileHandler;

    public function getAllParents(): JsonResponse
    {
        $parents = ParentModel::latest()->get();
        $counts = $parents->count();

        return response()->json([
            'parents' => $parents,
            'count' => $counts
        ]);
    }
    public function getUsersCompteActive(): JsonResponse
    {
        $users = User::where('compte_active', 1)
            ->latest()->get();
        $counts = $users->count();

        return response()->json([
            'users' => $users,
            'count' => $counts
        ]);
    }


    public function getUsersCompteInactive(): JsonResponse
    {
        $users = User::where('compte_active', 0)
            ->latest()->get();
        $counts = $users->count();

        return response()->json([
            'users' => $users,
            'count' => $counts
        ]);
    }


    public function getProfile(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'user' => $user
        ]);
    }

    public function getShowProfile(User $user): JsonResponse
    {
        return response()->json([
            'user' => $user
        ]);
    }


    public function UpdateProfile(UpdateProfileRequest  $request, ParentModel $parent): JsonResponse
    {
        $validated = $request->validated();
        DB::beginTransaction();
        try {
            $userUpdateData = [];
            $parentUpdateData = [];

            $userFields = ['name', 'avatar', 'phone_number', 'number_whatsapp'];
            $parentFields = ['name', 'avatar', 'gender', 'phone_number', 'number_whatsapp'];

            foreach ($validated as $key => $value) {
                if (in_array($key, $userFields)) {
                    $userUpdateData[$key] = $value;
                }
                if (in_array($key, $parentFields)) {
                    $parentUpdateData[$key] = $value;
                }
            }

            if (!empty($userUpdateData) && $parent->user_id) {
                $user = User::find($parent->user_id);
                if ($user) {
                    $user->update($userUpdateData);
                }
            }

            if ($request->hasFile('avatar')) {
                $avatar = $this->uploadFile($request->file('avatar'), 'avatars');
                $parent->avatar = $avatar;
            }

            if (!empty($parentUpdateData)) {
                $parent->update($parentUpdateData);
            }

            DB::commit();

            $parent->load('user');

            return response()->json([
                'message' => 'Enseignant mis à jour avec succès',
                'parent' => $parent->makeHidden('user'),
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


    public function updatePassword(StoreUpdatePasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = auth()->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Mot de passe actuel incorrect.',
                'status'  => 400,
            ], 400);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json([
            'message' => 'Mot de passe modifier.',
            'status'  => 200,
        ], 200);
    }


    public function logout(): JsonResponse
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            'message' => 'Vous être bien deconnecté. A bientot !',
            'status'  => 200,
        ], 200);
    }

    public function destroy(ParentModel $parent): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Récupérer l'utilisateur associé avant de supprimer le parent
            $user = $parent->user;

            if ($user) {
                // Supprimer tous les tokens de l'utilisateur
                $user->tokens()->delete();

                // Supprimer l'utilisateur (cela peut déclencher une suppression en cascade)
                $user->delete();
            }

            // Supprimer le parent
            $parent->delete();

            DB::commit();

            return response()->json([
                'message' => 'Parent et utilisateur supprimés avec succès.',
                'status' => '200'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la suppression du parent.',
                'error' => $e->getMessage(), // Optionnel : pour le débogage
                'status' => '500'
            ], 500);
        }
    }

    public function compteStatus(User $user): JsonResponse
    {
        $user->update([
            'compte_active' => !$user->compte_active
        ]);

        return response()->json([
            'message' => $user->compte_active ? 'Compte activé.' : 'Compte désactivé.',
            'status'  => 200,
        ], 200);
    }
}

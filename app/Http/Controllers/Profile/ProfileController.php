<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreUpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use App\Traits\FileHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use FileHandler;

    public function getAllUsers(): JsonResponse
    {
        $users = User::latest()->get();
        $counts = $users->count();

        return response()->json([
            'users' => $users,
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


    public function UpdateProfile(UpdateProfileRequest  $request): JsonResponse
    {
        $validated = $request->validated();
        $user = Auth::user();
        if($request->hasFile('avatar')) {
            if($user->avatar) {
                $this->deleteFile($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour',
            'user' => $user,
            'status' => '200'
        ],200);
    }


    public function updatePassword(StoreUpdatePasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = auth()->user();

        if(!Hash::check($validated['current_password'], $user->password)) {
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

    public function destroy(): JsonResponse
    {
        $user = auth()->user();
        $user->tokens()->delete();
        $user->delete();



        return response()->json([
            'message' => 'Utilisateur supprimé.',
            'status'  => 200,
        ], 200);
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

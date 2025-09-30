<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAvatarRequest;
use App\Http\Requests\Admin\UpdateAvatarRequest;
use App\Models\Avatar;
use Illuminate\Http\Request;
use App\Traits\FileHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AvatarController extends Controller
{
    use FileHandler;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $avatars = Avatar::with('admin')->latest()->get();
        return response()->json([
            'avatars' => $avatars,
            'status'   => 200,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAvatarRequest $request)
    {
        $validated = $request->validated();

        $uploadedAvatars = [];
        $adminId = Auth::user()->admin->id;

        DB::beginTransaction();

        try {
            if ($request->hasFile('avatars')) {
                foreach ($request->file('avatars') as $file) {
                    // Stocker chaque fichier
                    $avatarPath = $file->store('avatars', 'public');

                    // Créer l'enregistrement dans la base de données
                    $avatar = Avatar::create([
                        'admin_id' => $adminId,
                        'avatar' => $avatarPath,
                    ]);

                    $uploadedAvatars[] = $avatar->load('admin');
                }
            }

            DB::commit();

            return response()->json([
                'message' => count($uploadedAvatars) . ' avatar(s) créé(s) avec succès',
                'avatars' => $uploadedAvatars,
                'count' => count($uploadedAvatars),
                'status' => 200,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            // Supprimer les fichiers uploadés en cas d'erreur
            foreach ($uploadedAvatars as $avatar) {
                if (isset($avatar->avatar)) {
                    $this->deleteFile($avatar->avatar);
                }
            }

            return response()->json([
                'message' => 'Erreur lors de la création des avatars',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Avatar $avatar)
    {
        return response()->json([
            'avatar' => $avatar->load('admin'),
            'status' => 200,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAvatarRequest $request, Avatar $avatar)
    {
        $validated = $request->validated();

        if($request->hasFile('avatar')){
            if($avatar->avatar){
                $this->deleteFile($avatar->avatar);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        $avatar->update([
            'admin_id' => Auth::user()->admin->id,
            'avatar' => $validated['avatar'],
        ]);

        return response()->json([
            'message' => 'Avatar mis à jour avec succès',
            'avatar' => $avatar->load('admin'),
            'status' => 200,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Avatar $avatar)
    {
        if($avatar->avatar){
            $this->deleteFile($avatar->avatar);
        }
        $avatar->delete();

        return response()->json([
            'message' => 'Avatar supprimé avec succès',
            'status' => 200,
        ], 200);
    }
}

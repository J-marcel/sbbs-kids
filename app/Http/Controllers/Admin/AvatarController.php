<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAvatarRequest;
use App\Http\Requests\Admin\UpdateAvatarRequest;
use App\Models\Avatar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\FileHandler;
use Illuminate\Support\Facades\Auth;

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

        if($request->hasFile('avatar')){
            $avatar = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $avatar;
        }

        $avatar = Avatar::create([
            'admin_id' => Auth::user()->admin->id,
            'avatar' => $validated['avatar'],
        ]);

        return response()->json([
            'message' => 'Avatar créé avec succès',
            'avatar' => $avatar->load('admin'),
            'status' => 200,
        ], 200);


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

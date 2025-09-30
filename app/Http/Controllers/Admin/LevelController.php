<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLevelRequest;
use App\Http\Requests\Admin\UpdateLevelRequest;
use App\Models\Level;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class LevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $levels = Level::with('admin')
        ->latest()
        ->get();
        return response()->json([
            'levels' => $levels,
            'status' => 200,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLevelRequest $request): JsonResponse
    {
        $validated = $request->validated();
        if($request->hasFile('image')) {
            $image = $request->file('image')->store('images', 'public');
            $validated['image'] = $image;
        }
        $validated['admin_id'] = auth()->user()->id;
        $level = Level::create($validated);
        return response()->json([
            'message' => 'Niveau créé avec succès',
            'level' => $level->load('admin'),
            'status' => 200,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Level $level): JsonResponse
    {
        return response()->json([
            'message' => 'Niveau affiché avec succès',
            'level' => $level->load('admin'),
            'status' => 200,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLevelRequest $request, Level $level): JsonResponse
    {
        $validated = $request->validated();
        if($request->hasFile('image')) {
            if($level->image && Storage::disk('public')->exists($level->image)) {
                Storage::disk('public')->delete($level->image);
            }
            $image = $request->file('image')->store('images', 'public');
            $validated['image'] = $image;
        }
        $validated['admin_id'] = auth()->user()->id;
        $level->update($validated);
        return response()->json([
            'message' => 'Niveau modifié avec succès',
            'level' => $level->load('admin'),
            'status' => 200,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Level $level): JsonResponse
    {
        if($level->image && Storage::disk('public')->exists($level->image)) {
            Storage::disk('public')->delete($level->image);
        }
        $level->delete();
        return response()->json([
            'message' => 'Niveau supprimé avec succès',
            'status' => 200,
        ], 200);
    }
}

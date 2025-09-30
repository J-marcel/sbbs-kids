<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSupportRequest;
use App\Http\Requests\Admin\UpdateSupportRequest;
use App\Models\Support;
use Illuminate\Http\JsonResponse;

class SupportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $supports = Support::with('admin')
        ->latest()
        ->get();
        return response()->json([
            'supports' => $supports,
            'status' => 200,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['admin_id'] = auth()->user()->id;
        $support = Support::create($validated);
        return response()->json([
            'message' => 'Support créé avec succès',
            'support' => $support->load('admin'),
            'status' => 200,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Support $support): JsonResponse
    {
        return response()->json([
            'message' => 'Support affiché avec succès',
            'support' => $support->load('admin'),
            'status' => 200,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupportRequest $request, Support $support): JsonResponse
    {
        $validated = $request->validated();
        $validated['admin_id'] = auth()->user()->id;
        $support->update($validated);
        return response()->json([
            'message' => 'Support modifié avec succès',
            'support' => $support->load('admin'),
            'status' => 200,
        ], 200);
    }

    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Support $support): JsonResponse
    {
        $support->delete();
        return response()->json([
            'message' => 'Support supprimé avec succès',
            'status' => 200,
        ], 200);
    }
}

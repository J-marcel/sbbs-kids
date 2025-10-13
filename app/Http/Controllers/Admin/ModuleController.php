<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreModuleRequest;
use App\Models\Course;
use App\Models\Module;
use App\Models\Support;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ModuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $modules = Module::with('level', 'admin', 'courses')
            ->latest()
            ->get();

        return response()->json([
            'modules' => $modules,
            'status' => 200,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreModuleRequest $request)
    {
        try {
            DB::beginTransaction();
            $validated = $request->validated();


            // Créer le module
            $module = Module::create([
                'name' => $validated['name'],
                'applications' => $validated['applications'],
                'level_id' => $validated['level_id'],
                'admin_id' => auth()->user()->id,
            ]);



            DB::commit();

            return response()->json([
                'message' => 'Module, cours et supports créés avec succès',
                'module' => $module->load('level', 'admin'),
                'status' => 201,
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la création: ' . $th->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Module $module)
    {
        $module->load('level', 'admin', 'courses');

        return response()->json([
            'module' => $module,
            'status' => 200,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreModuleRequest $request, Module $module)
    {
        try {
            DB::beginTransaction();
            $validated = $request->validated();

            // Mettre à jour le module
            $module->update([
                'name' => $validated['name'],
                'applications' => $validated['applications'],
                'image' => $validated['image'],
                'level_id' => $validated['level_id'],
                // 'support_id' => $validated['support_id'],
                'admin_id' => auth()->user()->id,
            ]);



            DB::commit();

            return response()->json([
                'message' => 'Module, cours et supports mis à jour avec succès',
                'module' => $module->load('level', 'admin', 'courses'),
                'status' => 200,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la mise à jour: ' . $th->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Module $module)
    {
        try {
            DB::beginTransaction();

            // Supprimer le module (les cours et supports seront supprimés en cascade si configuré)
            $module->delete();

            DB::commit();

            return response()->json([
                'message' => 'Module supprimé avec succès',
                'status' => 200,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la suppression: ' . $th->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}

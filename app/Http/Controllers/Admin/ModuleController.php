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
     * Display modules grouped by age_group
     */
    public function getByAgeGroup(Request $request)
    {
        try {
            // Récupérer tous les modules avec leurs relations
            $modules = Module::with(['level', 'admin', 'courses'])
                ->get()
                ->groupBy(function ($module) {
                    return $module->level->age_group;
                });

            // Formater le résultat
            $result = [];
            foreach ($modules as $ageGroup => $groupModules) {
                $result[] = [
                    'age_group' => $ageGroup,
                    'modules' => $groupModules
                ];
            }

            return response()->json([
                'data' => $result,
                'status' => 200,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Erreur lors de la récupération: ' . $th->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Display modules by specific age_group
     */
    public function getBySpecificAgeGroup(string $ageGroup)
    {
        try {
            // Valider l'age_group
            $validAgeGroups = ['4-7', '8-12', '13-17'];

            if (!in_array($ageGroup, $validAgeGroups)) {
                return response()->json([
                    'message' => 'Groupe d\'âge invalide',
                    'status' => 400,
                ], 400);
            }

            // Récupérer les modules pour ce groupe d'âge
            $modules = Module::with(['level', 'admin', 'courses'])
                ->whereHas('level', function ($query) use ($ageGroup) {
                    $query->where('age_group', $ageGroup);
                })
                ->get();

            return response()->json([
                'age_group' => $ageGroup,
                'modules' => $modules,
                'count' => $modules->count(),
                'status' => 200,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Erreur lors de la récupération: ' . $th->getMessage(),
                'status' => 500,
            ], 500);
        }
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

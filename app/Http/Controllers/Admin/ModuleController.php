<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreModuleRequest;
use App\Models\Course;
use App\Models\Module;
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
        $modules = Module::with('level', 'admin', 'support', 'courses')
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

            if($request->hasFile('image')) {
                $image = $request->file('image')->store('images', 'public');
                $validated['image'] = $image;
            }
            $module = Module::create([
                'name' => $validated['name'],
                'applications' => $validated['applications'],
                'image' => $validated['image'],
                'level_id' => $validated['level_id'],
                'support_id' => $validated['support_id'],
                'admin_id' => auth()->user()->id,
            ]);

            $createdCourses = [];

            // Créer les cours associés
            foreach ($request->courses as $index => $courseData) {
                $course = [
                    'title' => $courseData['title'],
                    'duration' => $courseData['duration'],
                    'competences' => $courseData['competences'],
                    'price' => $courseData['price'],
                    'libelle' => $courseData['libelle'],
                    'module_id' => $module->id,
                    'admin_id' => auth()->user()->id,
                ];


                $videoUrl = $courseData['video'] ?? null;
                $course['video'] = $videoUrl;

                $createdCourses[] = Course::create($course);
            }

            DB::commit();

            return response()->json([
                'message' => 'Module et cours créés avec succès',
                'module' => $module->load('level', 'admin', 'support', 'courses'),
                'courses' => $createdCourses,
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
        $module->load('level', 'admin', 'support', 'courses');

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

            $module->update([
                'name' => $validated['name'],
                'applications' => $validated['applications'],
                'image' => $validated['image'],
                'level_id' => $validated['level_id'],
                'support_id' => $validated['support_id'],
                'admin_id' => auth()->user()->id,
            ]);

            $module->courses()->delete();

            $createdCourses = [];

            // Créer les cours associés
            foreach ($request->courses as $index => $courseData) {
                $course = [
                    'title' => $courseData['title'],
                    'duration' => $courseData['duration'],
                    'competences' => $courseData['competences'],
                    'price' => $courseData['price'],
                    'libelle' => $courseData['libelle'],
                    'module_id' => $module->id,
                    'admin_id' => auth()->user()->id,
                ];

                $videoUrl = $courseData['video'] ?? null;
                $course['video'] = $videoUrl;

                $createdCourses[] = Course::create($course);
            }

            DB::commit();

            return response()->json([
                'message' => 'Module et cours mis à jour avec succès',
                'module' => $module->load('level', 'admin', 'support', 'courses'),
                'courses' => $createdCourses,
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

                // Supprimer l'image associée si elle existe
                if ($module->image && Storage::disk('public')->exists($module->image)) {
                    Storage::disk('public')->delete($module->image);
                }

                // Supprimer le module (les cours seront supprimés en cascade si configuré)
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

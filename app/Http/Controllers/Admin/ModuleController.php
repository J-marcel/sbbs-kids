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
        $modules = Module::with('level', 'admin', 'supports', 'courses')
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

            // Gérer l'image du module
            if ($request->hasFile('image')) {
                $image = $request->file('image')->store('images', 'public');
                $validated['image'] = $image;
            }

            // Créer le module
            $module = Module::create([
                'name' => $validated['name'],
                'applications' => $validated['applications'],
                'image' => $validated['image'],
                'level_id' => $validated['level_id'],
                'admin_id' => auth()->user()->id,
            ]);

            $createdCourses = [];
            $createdSupports = [];

            // Créer les cours associés
            foreach ($request->courses as $courseData) {
                $course = Course::create([
                    'title' => $courseData['title'],
                    'duration' => $courseData['duration'],
                    'competences' => $courseData['competences'],
                    'price' => $courseData['price'],
                    'libelle' => $courseData['libelle'],
                    'video' => $courseData['video'],
                    'module_id' => $module->id,
                    'admin_id' => auth()->user()->id,
                ]);
                $createdCourses[] = $course;
            }

            // Créer les supports associés
            foreach ($request->supports as $index => $supportData) {
                $pdfPath = null;

                // Gérer le fichier PDF s'il est présent
                if ($request->hasFile("supports.{$index}.pdf")) {
                    $pdfPath = $request->file("supports.{$index}.pdf")->store('supports/pdf', 'public');
                }

                $support = Support::create([
                    'libelle' => $supportData['libelle'],
                    'pdf' => $pdfPath,
                    'video' => $supportData['video'] ?? null,
                    'description' => $supportData['description'] ?? null,
                    'module_id' => $module->id,
                    'admin_id' => auth()->user()->id,
                ]);
                $createdSupports[] = $support;
            }

            DB::commit();

            return response()->json([
                'message' => 'Module, cours et supports créés avec succès',
                'module' => $module->load('level', 'admin', 'supports', 'courses', 'supports'),
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
        $module->load('level', 'admin', 'supports', 'courses', 'supports');

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

            // Gérer la nouvelle image si fournie
            if ($request->hasFile('image')) {
                // Supprimer l'ancienne image
                if ($module->image && Storage::disk('public')->exists($module->image)) {
                    Storage::disk('public')->delete($module->image);
                }
                $image = $request->file('image')->store('images', 'public');
                $validated['image'] = $image;
            } else {
                $validated['image'] = $module->image;
            }

            // Mettre à jour le module
            $module->update([
                'name' => $validated['name'],
                'applications' => $validated['applications'],
                'image' => $validated['image'],
                'level_id' => $validated['level_id'],
                // 'support_id' => $validated['support_id'],
                'admin_id' => auth()->user()->id,
            ]);

            // Mettre à jour les cours (supprimer et recréer)
            $module->courses()->delete();
            foreach ($request->courses as $courseData) {
                Course::create([
                    'title' => $courseData['title'],
                    'duration' => $courseData['duration'],
                    'competences' => $courseData['competences'],
                    'price' => $courseData['price'],
                    'libelle' => $courseData['libelle'],
                    'video' => $courseData['video'],
                    'module_id' => $module->id,
                    'admin_id' => auth()->user()->id,
                ]);
            }

            // Mettre à jour les supports (supprimer et recréer)
            $module->supports()->delete();
            foreach ($request->supports as $index => $supportData) {
                $pdfPath = null;

                // Gérer le fichier PDF s'il est présent
                if (isset($supportData['pdf']) && $request->hasFile("supports.{$index}.pdf")) {
                    $pdfPath = $request->file("supports.{$index}.pdf")->store('supports/pdf', 'public');
                }

                Support::create([
                    'libelle' => $supportData['libelle'],
                    'pdf' => $pdfPath,
                    'video' => $supportData['video'] ?? null,
                    'description' => $supportData['description'] ?? null,
                    'module_id' => $module->id,
                    'admin_id' => auth()->user()->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Module, cours et supports mis à jour avec succès',
                'module' => $module->load('level', 'admin', 'supports', 'courses', 'supports'),
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

            // Supprimer les fichiers PDF des supports
            foreach ($module->supports as $support) {
                if ($support->pdf && Storage::disk('public')->exists($support->pdf)) {
                    Storage::disk('public')->delete($support->pdf);
                }
            }

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

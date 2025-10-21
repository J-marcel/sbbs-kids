<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Support;
use App\Models\Activity;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CoursesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $courses = Course::with(['module',  'supports', 'activities', 'workshops', 'admin'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'status' => 200,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCourseRequest $request)
    {
        DB::beginTransaction();

        try {
            // Créer le cours
            $course = Course::create([
                'title' => $request->title,
                'libelle' => $request->libelle,
                'objectif' => $request->objectif,
                'guide_for_parents' => $request->guide_for_parents,
                'introduction' => $request->introduction,
                'conclusion' => $request->conclusion,
                'module_id' => $request->module_id,
                'admin_id' => auth()->user()->id,
            ]);

            // Créer les supports
            if ($request->has('supports')) {
                foreach ($request->supports as $supportData) {
                    $pdfPath = null;

                    // Gérer l'upload du PDF
                    if (isset($supportData['pdf']) && $supportData['pdf']) {
                        $pdfPath = $supportData['pdf']->store('courses/supports', 'public');
                    }

                    Support::create([
                        'course_id' => $course->id,
                        'admin_id' => auth()->user()->id,
                        'libelle' => $supportData['libelle'],
                        'pdf' => $pdfPath,
                        'video' => $supportData['video'] ?? null,
                        'description' => $supportData['description'] ?? null,
                    ]);
                }
            }

            // Créer les activités
            if ($request->has('activities')) {
                foreach ($request->activities as $activityData) {
                    Activity::create([
                        'course_id' => $course->id,
                        'admin_id' => auth()->user()->id,
                            'title' => $activityData['title'],
                        'libelle' => $activityData['libelle'] ?? null,
                        'description' => $activityData['description'] ?? null,

                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cours créé avec succès.',
                'status' => 200,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du cours : ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course)
    {
        $course->load(['module',  'supports', 'activities', 'workshops', 'admin']);

        return response()->json([
            'success' => true,
            'course' => $course,
            'status' => 200,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        DB::beginTransaction();

        try {
            // Mettre à jour le cours
            $course->update([
                'title' => $request->title ?? $course->title,
                'libelle' => $request->libelle ?? $course->libelle,
                'objectif' => $request->objectif ?? $course->objectif,
                'guide_for_parents' => $request->guide_for_parents ?? $course->guide_for_parents,
                'introduction' => $request->introduction ?? $course->introduction,
                'conclusion' => $request->conclusion ?? $course->conclusion,
                'module_id' => $request->module_id ?? $course->module_id,
                'admin_id' => auth()->user()->id,

            ]);

            // Mettre à jour les supports si présents
            if ($request->has('supports')) {
                // Supprimer les anciens supports et leurs fichiers
                foreach ($course->supports as $oldSupport) {
                    if ($oldSupport->pdf) {
                        Storage::disk('public')->delete($oldSupport->pdf);
                    }
                    $oldSupport->delete();
                }

                // Créer les nouveaux supports
                foreach ($request->supports as $supportData) {
                    $pdfPath = null;

                    if (isset($supportData['pdf']) && $supportData['pdf']) {
                        $pdfPath = $supportData['pdf']->store('courses/supports', 'public');
                    }

                    Support::create([
                        'course_id' => $course->id,
                        'admin_id' => auth()->user()->id,
                        'libelle' => $supportData['libelle'],
                        'pdf' => $pdfPath,
                        'video' => $supportData['video'] ?? null,
                        'description' => $supportData['description'] ?? null,
                    ]);
                }
            }

            // Mettre à jour les activités si présentes
            if ($request->has('activities')) {
                // Supprimer les anciennes activités
                $course->activities()->delete();

                // Créer les nouvelles activités
                foreach ($request->activities as $activityData) {
                    Activity::create([
                        'course_id' => $course->id,
                        'admin_id' => auth()->user()->id,
                            'title' => $activityData['title'],
                        'libelle' => $activityData['libelle'] ?? null,
                        'description' => $activityData['description'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cours mis à jour avec succès.',
                'status' => 200,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du cours : ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        try {
            // Supprimer les fichiers PDF des supports
            foreach ($course->supports as $support) {
                if ($support->pdf) {
                    Storage::disk('public')->delete($support->pdf);
                }
            }

            // Supprimer le cours (les supports et activités seront supprimés en cascade)
            $course->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cours supprimé avec succès.',
                'status' => 200,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du cours : ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWorkshopRequest;
use App\Http\Requests\Admin\UpdateWorkshopRequest;
use App\Models\Workshop;
use Illuminate\Support\Facades\DB;

class WorkshopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $workshops = Workshop::with('course', 'admin')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'workshops' => $workshops,
            'status' => 200,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWorkshopRequest $request)
    {
        DB::beginTransaction();

        try {
            $workshop = Workshop::create([
                'title' => $request->title,
                'educational_objective' => $request->educational_objective,
                'required_equipment' => $request->required_equipment,
                'activity_schedule' => $request->activity_schedule,
                'course_id' => $request->course_id,
                'admin_id' => auth()->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Atelier créé avec succès.',
                'status' => 200,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'atelier : ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Workshop $workshop)
    {
        return response()->json([
            'success' => true,
            'workshop' => $workshop->load('course', 'admin'),
            'status' => 200,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWorkshopRequest $request, Workshop $workshop)
    {
        DB::beginTransaction();

        try {
            $workshop->update([
                'title' => $request->title ?? $workshop->title,
                'educational_objective' => $request->educational_objective ?? $workshop->educational_objective,
                'required_equipment' => $request->required_equipment ?? $workshop->required_equipment,
                'activity_schedule' => $request->activity_schedule ?? $workshop->activity_schedule,
                'course_id' => $request->course_id ?? $workshop->course_id,
                'admin_id' => auth()->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Atelier mis à jour avec succès.',
                'status' => 200,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'atelier : ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Workshop $workshop)
    {
        try {
            $workshop->delete();

            return response()->json([
                'success' => true,
                'message' => 'Atelier supprimé avec succès.',
                'status' => 200,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'atelier : ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}

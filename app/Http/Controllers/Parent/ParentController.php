<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $parents = ParentModel::latest()->get();
        return response()->json([
            'parents' => $parents,
            'status'   => 200,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       abort(403);
    }

    /**
     * Display the specified resource.
     */
    public function show(ParentModel $parentModel)
    {
        return response()->json([
            'parent' => $parentModel,
            'status'   => 200,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ParentModel $parentModel)
    {
        // $validated = $request->validated();
        // DB::beginTransaction();

        // $parentModel->update($validated);

        // DB::commit();

        // return response()->json([
        //     'message' => 'Parent mis à jour avec succès',
        //     'parent' => $parentModel,
        //     'status'  => 200,
        // ], 200);

        abort(403);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ParentModel $parentModel)
    {
        DB::beginTransaction();

        $parentModel->delete();

        DB::commit();

        return response()->json([
            'message' => 'Parent supprimé avec succès',
            'status'  => 200,
        ], 200);
    }
}

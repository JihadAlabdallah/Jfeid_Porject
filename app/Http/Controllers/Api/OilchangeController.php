<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OilChange;
use Illuminate\Http\Request;

class OilchangeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Fetch all oil change records associated with the authenticated user
        $oilChanges = OilChange::all(); // Replace with actual logic to fetch oil changes

        // Return the oil change records as a JSON response
        return response()->json([
            'status' => 'success',
            'data' => $oilChanges
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // This method can be used to show a form for creating a new oil change record
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'record_id' => 'required|exists:maintenance_records,id',
            'current_km' => 'nullable|string|max:255',
            'oil_type' => 'nullable|string|max:255',
            'oil_quantity' => 'nullable|string|max:255',
            'filter' => 'nullable|string|max:255',
            'next_change_km' => 'nullable|string',
            'distance_unit' => 'nullable|in:km,miles',
        ]);

        // Create a new oil change record
        $oilChanges = OilChange::create([
            'record_id' => $request->record_id,
            'current_km' => $request->current_km,
            'oil_type' => $request->oil_type,
            'oil_quantity' => $request->oil_quantity,
            'filter' => $request->filter,
            'next_change_km' => $request->next_change_km,
            // empty = the car's unit; empty next change = current + 5,000 km / 3,107 mi
            'distance_unit' => $request->distance_unit,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Oil change record created successfully.',
            'data' => $oilChanges
        ], 201);
    }
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $oilChanges = OilChange::find($id); // Replace with actual logic to fetch an oil change record by ID

        // Return the oil change record as a JSON response
        return response()->json([
            'status' => 'success',
            'data' => $oilChanges
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $oilChanges = OilChange::find($id);
        // Validate the request data
        $request->validate([
            'current_km' => 'nullable|string|max:255',
            'oil_type' => 'nullable|string|max:255',
            'oil_quantity' => 'nullable|string|max:255',
            'filter' => 'nullable|string|max:255',
            'next_change_km' => 'nullable|string',
            'distance_unit' => 'nullable|in:km,miles',
        ]);

        if (! $oilChanges) {
            return response()->json(['status' => 'error', 'message' => 'Oil change not found.'], 404);
        }

        // this used to return "updated" without saving anything
        $oilChanges->update($request->only([
            'current_km', 'oil_type', 'oil_quantity', 'filter', 'next_change_km', 'distance_unit',
        ]));

        // Return the updated oil change record as a JSON response
        return response()->json([
            'status' => 'success',
            'message' => 'Oil change record updated successfully.',
            'data' => $oilChanges
        ]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $oilChanges = OilChange::find($id); // Replace with actual logic to fetch an oil change record by ID
        // Check if the oil change record exists

        OilChange::destroy($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Oil change record deleted successfully.'
        ]);
    }
}

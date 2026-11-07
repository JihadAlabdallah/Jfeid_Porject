<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRecord;
use Illuminate\Http\Request;

class MaintenanceRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Fetch all maintenance records with all related services to prevent null issues in mobile app
        $maintenanceRecord = MaintenanceRecord::with([
            'vehicle',
            'batteryService',
            'oilChange',
            'steeringOilChange',
            'wheelService',
            'gearOilChange',
            'transmissionOilChange',
            'differentialOilChange',
            'otherService'
        ])->get();

        // Return the maintenance records as a JSON response
        return response()->json([
            'status' => 'success',
            'data' => $maintenanceRecord
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // This method can be used to show a form for creating a new maintenance record
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'maintenance_date' => 'required|date',
            'type' => 'required|string|max:255',
            // optional odometer reading, saved in the car's unit
            'current_reading' => 'nullable|integer|min:0|max:4294967295',
            // optional; filled automatically from the reading when the type has a standard interval
            'next_change_reading' => 'nullable|integer|min:0|max:4294967295',

        ]);

        // Create a new maintenance record
        $maintenanceRecord =  MaintenanceRecord::create([
            'vehicle_id' => $request->vehicle_id,
            'maintenance_date' => $request->maintenance_date,
            'type' => $request->type,
            'current_reading' => $request->current_reading,
            'next_change_reading' => $request->next_change_reading,

        ]);

        // Return the created maintenance record as a JSON response
        return response()->json([
            'status' => 'success',
            'message' => 'Maintenance record created successfully.',
            'data' => $maintenanceRecord
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Fetch the specific maintenance record with all relationships (for Flutter type safety)
        $maintenanceRecord = MaintenanceRecord::with([
            'vehicle',
            'batteryService',
            'oilChange',
            'steeringOilChange',
            'wheelService',
            'gearOilChange',
            'transmissionOilChange',
            'differentialOilChange',
            'otherService'
        ])->findOrFail($id);
       // $maintenanceRecord->makeHidden(['created_at', 'updated_at']);
//$maintenanceRecord->user->makeHidden(['created_at', 'updated_at']);
        // Return the maintenance record as a JSON response
        return response()->json([
            'status' => 'success',
            'data' => $maintenanceRecord
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate the request data
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'maintenance_date' => 'required|date',
            'type' => 'required|string|max:255',
            // optional odometer reading, saved in the car's unit
            'current_reading' => 'nullable|integer|min:0|max:4294967295',
            // optional; filled automatically from the reading when the type has a standard interval
            'next_change_reading' => 'nullable|integer|min:0|max:4294967295',
        ]);

        // Fetch the specific maintenance record by
        $maintenanceRecord =  MaintenanceRecord::findOrFail($id);
        // Update the maintenance record with the provided data
        $maintenanceRecord->update($request->only(['vehicle_id', 'maintenance_date', 'type', 'current_reading', 'next_change_reading']));
        // Return the updated maintenance record as a JSON response 

        return response()->json([
            'status' => 'success',
            'message' => 'Maintenance record updated successfully.',
            'data' => $maintenanceRecord
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Fetch the specific maintenance record by ID
        $maintenanceRecord =  MaintenanceRecord::findOrFail($id);
        // Delete the maintenance record
        $maintenanceRecord->delete();

        // Return a success response
        return response()->json([
            'status' => 'success',
            'message' => 'Maintenance record deleted successfully.'
        ]);
    }
}

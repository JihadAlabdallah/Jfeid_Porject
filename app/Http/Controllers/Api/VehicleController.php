<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Support\Distance;
use App\Traits\HideTimestampsTrait;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VehicleController extends Controller
{
    use HideTimestampsTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Fetch all vehicles associated with the authenticated user
        $vehicle = Vehicle::all();

        // Return the vehicles as a JSON response
        return response()->json([
            'status' => 'success',
            'data' => $vehicle
        ]);
    }

    /**
     * GET /my-vehicles: only the signed-in user's vehicles.
     */
    public function mine(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()->vehicles()->orderBy('id')->get(),
        ]);
    }

    /**
     * POST /my-vehicles: add another vehicle to the signed-in user.
     */
    public function storeMine(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|max:255',
            'distance_unit' => 'sometimes|in:' . implode(',', Distance::UNITS),
        ]);

        $vehicle = $request->user()->vehicles()->create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle created successfully.',
            'data' => $vehicle,
        ], 201);
    }

    /**
     * PATCH /my-vehicles/{id}: rename a vehicle or switch it between km and
     * miles. Only the signed-in user's own vehicles. Old oil changes keep the
     * unit they were recorded in.
     */
    public function updateMine(Request $request, string $id)
    {
        $vehicle = $request->user()->vehicles()->find($id);

        if (! $vehicle) {
            return response()->json(['status' => 'error', 'message' => 'Vehicle not found.'], 404);
        }

        $data = $request->validate([
            'type' => 'sometimes|string|max:255',
            'distance_unit' => 'sometimes|in:' . implode(',', Distance::UNITS),
        ]);

        $vehicle->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle updated successfully.',
            'data' => $vehicle->fresh(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:255',
        ]);

        $vehicle = Vehicle::create([ 
            'user_id' => $request->user_id,
            'type' => $request->type,
        ]);

        // Return the created vehicle as a JSON response
        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle created successfully.',
            'data' => $vehicle
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
{
    $vehicle = Vehicle::with(['user', 'maintenanceRecords'])->find($id);

    if (!$vehicle) {
        return response()->json(['error' => 'Vehicle not found'], 404);
    }

    $this->hideTimestampsRecursively($vehicle);

    $grouped = [];

    $vehicle->maintenanceRecords->each(function ($record) use (&$grouped) {
        $relation = match ($record->type) {
            'battery_service' => 'batteryService',
            'wheel_service' => 'wheelService',
            'gear_oil_change' => 'gearOilChange',
            'steering_oil_change' => 'steeringOilChange',
            'transmission_oil_change' => 'transmissionOilChange',
            'differential_oil_change' => 'differentialOilChange',
            'oil_change' => 'oilChange',
            'other' => 'otherService',
            default => null,
        };

        $related = null;
        if ($relation) {
            $record->load($relation);
            $related = $record->{$relation};
            if ($related) {
                $related->makeHidden(['created_at', 'updated_at']);

                // added fields: who did the service and what it cost (null on old records)
                $related->setAttribute('technician_name', $record->technician_name);
                $related->setAttribute('price', $record->price);

                // odometer at the service, in the unit it was entered in (reading may be null)
                foreach ($record->reading() as $key => $value) {
                    $related->setAttribute($key, $value);
                }
            }
        }

        $date = $record->maintenance_date
            ? Carbon::parse($record->maintenance_date)->toDateString()
            : 'no-date';

        if (!isset($grouped[$date])) {
            $grouped[$date] = [
                'date' => $date,
                'maintenance' => []
            ];
        }

        // كل سجل في التاريخ الخاص به (لنفس النوع أو غيره) - Return null explicitly instead of empty object for Flutter type safety
        $grouped[$date]['maintenance'][$record->type] = $related;
    });

    ksort($grouped);

    return response()->json([
        'status' => 'success',
        'data' => [
            'id' => $vehicle->id,
            'user_id' => $vehicle->user_id,
            'type' => $vehicle->type,
            'distance_unit' => $vehicle->distance_unit,
            'user' => $vehicle->user,
            'maintenance_records' => array_values($grouped)
        ]
    ]);
}




    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate the request data
        $request->validate([
            'type' => 'required|string|max:255',
        ]);

        // Find the vehicle by ID
        $vehicle = Vehicle::find($id);

        // Check if the vehicle exists
        if (!$vehicle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vehicle not found.'
            ], 404);
        }

        // Update the vehicle's type
        $vehicle->update([
            'type' => $request->type,
        ]);

        // Return the updated vehicle as a JSON response
        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle updated successfully.',
            'data' => $vehicle
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Find the vehicle by ID
        $vehicle = Vehicle::find($id);

        // Check if the vehicle exists
        if (!$vehicle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vehicle not found.'
            ], 404);
        }

        // Delete the vehicle
        $vehicle->delete();

        // Return a success response
        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle deleted successfully.'
        ]);
    }
}

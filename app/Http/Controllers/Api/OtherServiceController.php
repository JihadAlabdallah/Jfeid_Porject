<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtherService;
use Illuminate\Http\Request;

/**
 * Details for "other" maintenance records (custom services), same pattern as
 * the other detail endpoints: create the maintenance record with
 * type "other" first, then send these details with its record_id.
 */
class OtherServiceController extends Controller
{
    private const RULES = [
        'category' => 'nullable|string|max:255',
        'description' => 'nullable|string|max:5000',
        'note' => 'nullable|string|max:5000',
        'quantity' => 'nullable|numeric|min:0',
    ];

    public function index()
    {
        return response()->json(['status' => 'success', 'data' => OtherService::all()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'record_id' => 'required|exists:maintenance_records,id',
            'name' => 'required|string|max:255',
        ] + self::RULES);

        $service = OtherService::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Service created successfully.',
            'data' => $service,
        ], 201);
    }

    public function show($id)
    {
        $service = OtherService::find($id);

        if (! $service) {
            return response()->json(['status' => 'error', 'message' => 'Service not found.'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $service]);
    }

    public function update(Request $request, $id)
    {
        $service = OtherService::find($id);

        if (! $service) {
            return response()->json(['status' => 'error', 'message' => 'Service not found.'], 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
        ] + self::RULES);

        $service->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Service updated successfully.',
            'data' => $service->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $service = OtherService::find($id);

        if (! $service) {
            return response()->json(['status' => 'error', 'message' => 'Service not found.'], 404);
        }

        $service->delete();

        return response()->json(['status' => 'success', 'message' => 'Service deleted successfully.']);
    }
}

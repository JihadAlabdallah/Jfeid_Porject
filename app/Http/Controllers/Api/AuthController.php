<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function signup(Request $request)
    {

        // validate data from user
        $request->validate([
            "name" => "required|string",
            "email" => "email|unique:users",
            "phonenumber" => "required|string",
            "password" => "required|min:3",
            "carplate" => "nullable|string",
            "vehicle_type" => "nullable|string",
        ]);

        // create new user and save it to database
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phonenumber' => $request->phonenumber,
            'password' => Hash::make($request->password),
            'carplate' => $request->carplate,
        ]);

        // create the user's vehicle so login never returns vehicle_id 0.
        // uses the vehicle_type sent from the app, falling back to a default.
        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'type'    => $request->vehicle_type ?: 'My Vehicle',
        ]);


        // Check if the request is coming from a Flutter application
        if ($request->header('User-Agent') === 'Flutter') {
            $access_token = $user->createToken('authToken')->plainTextToken;
            return response()->json([
                'status' => '201',
                'message' => 'User has been created successfully.',
                'token' => $access_token,
                'vehicle_id' => $vehicle->id
            ], 201);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'invalid request'
            ]);
        }
    }
    public function signin(Request $request)
    {
        Log::info('Login request received', ['request' => $request->except('password')]);

        $credentials = $request->validate([
            'name_or_email' => 'required|string',
            'password' => 'required|string|min:3',
        ]);

        $user = User::where('email', $credentials['name_or_email'])
            ->orWhere('name', $credentials['name_or_email'])
            ->first();

        if ($user && Hash::check($request->password, $user->password)) {
            $access_token = $user->createToken("authToken")->plainTextToken;

            // get first vehicle id - default to 0 if user has no vehicles (for mobile app type safety)
            $vehicle_id = $user->vehicles()->first()?->id ?? 0;

            return response()->json([
                'success' => true,
                'message' => 'Logged In Successfully!',
                'vehicle_id' => $vehicle_id,
                // all of the user's vehicles, for clients with more than one
                'vehicles' => $user->vehicles()->orderBy('id')->get(['id', 'type', 'distance_unit']),
                'token' => $access_token
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Username/Email or Password'
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        return response()->json(['message' => 'Successfully logged out']);
    }
    public function getProfile(Request $request)
    {
        // Return the authenticated user's profile
        return response()->json([
            'status' => 'success',
            'data' => $request->user()
        ]);
    }
    /**
     * Update the signed-in user's profile (name, email, phone number, car plate).
     * Only the fields that are sent are changed. (KM/miles is set per car.)
     *
     * Route: POST /profile/update/{token}. The {token} segment is kept for
     * compatibility with the app; authentication comes from the Bearer token.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // accept the spellings the app has used for the phone field
        foreach (['Phonenumber', 'phone_number', 'phone'] as $alias) {
            if ($request->has($alias) && ! $request->has('phonenumber')) {
                $request->merge(['phonenumber' => $request->input($alias)]);
            }
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|max:255|unique:users,email,' . $user->id,
            'phonenumber' => 'sometimes|string|max:20',
            'carplate' => 'sometimes|nullable|string|max:20',
        ]);

        $user->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
            'data' => $user->fresh(),
        ]);
    }

    public function destroy(Request $request)
    {
        $user = \App\Models\User::find(Auth::id());

        if ($user) {
            // Optionally revoke tokens if using Sanctum
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            // Delete the user
            $user->delete();

            return response()->json(['message' => 'User deleted successfully.'], 200);
        }

        return response()->json(['error' => 'User not found.'], 404);
    }
}

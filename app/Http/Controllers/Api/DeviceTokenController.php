<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

/**
 * The app registers the phone's Firebase token after login (and whenever
 * Firebase gives it a new one), so the dashboard can send to chosen customers.
 */
class DeviceTokenController extends Controller
{
    /** POST /device-tokens  { token, platform? } */
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string|max:512',
            'platform' => 'nullable|string|in:android,ios',
        ]);

        // a phone belongs to whoever signed in on it last
        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return response()->json(['status' => 'success', 'message' => 'Device registered.']);
    }

    /** DELETE /device-tokens  { token }: on logout, so the phone stops getting this customer's messages */
    public function destroy(Request $request)
    {
        $data = $request->validate(['token' => 'required|string|max:512']);

        DeviceToken::where('token', $data['token'])->where('user_id', $request->user()->id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Device removed.']);
    }
}

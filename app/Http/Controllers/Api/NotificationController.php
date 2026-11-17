<?php

namespace App\Http\Controllers\Api;

use Google\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Notification;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function sendNotificationV1(Request $request)
    {
        if (! $request->user()?->is_admin) {
            return response()->json(['error' => 'Only admins can send notifications.'], 403);
        }

        $request->validate([
            'topic' => 'required|string',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $topic = $request->input('topic');
        $title = $request->input('title');
        $body = $request->input('body');

        try {
            $firebaseConfig = $this->getFirebaseConfig();
            $client = $this->buildFirebaseClient($firebaseConfig);

         
            $tokenData = $client->fetchAccessTokenWithAssertion();
            if (!isset($tokenData['access_token'])) {
                return response()->json([
                    'error' => 'Failed to fetch access token. Check your Firebase credentials.'
                ], 500);
            }
            $accessToken = $tokenData['access_token'];

            $projectId = (string) ($firebaseConfig['project_id'] ?? '');
            if ($projectId === '') {
                return response()->json([
                    'error' => 'Firebase project_id is missing in FIREBASE_CREDENTIALS.'
                ], 500);
            }

           
            $payload = [
                'message' => [
                    'topic' => $topic,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'message' => $body,
                    ],
                ],
            ];

            
            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

         
            Notification::create([
                'topic' => $topic,
                'title' => $title,
                'body' => $body,
                'response' => json_encode($response->json()), 
            ]);

         
            if ($response->successful()) {
                return response()->json(['success' => 'Notification sent successfully 🎉']);
            } else {
                return response()->json(['error' => 'FCM Error: ' . $response->body()], 500);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed: ' . $e->getMessage()], 500);
        }
    }


    private function getAccessToken(): string
    {
        $firebaseConfig = $this->getFirebaseConfig();
        $client = $this->buildFirebaseClient($firebaseConfig);

        $token = $client->fetchAccessTokenWithAssertion();
        return $token['access_token'] ?? '';
    }

    private function getFirebaseConfig(): array
    {
        $credentialsBase64 = (string) config('services.firebase.credentials');
        if ($credentialsBase64 === '') {
            throw new \RuntimeException('FIREBASE_CREDENTIALS is not configured.');
        }

        $decodedCredentials = base64_decode($credentialsBase64, true);
        if ($decodedCredentials === false) {
            throw new \RuntimeException('FIREBASE_CREDENTIALS must be valid base64.');
        }

        $credentials = json_decode($decodedCredentials, true);
        if (!is_array($credentials)) {
            throw new \RuntimeException('FIREBASE_CREDENTIALS must decode to valid JSON.');
        }

        return $credentials;
    }

    private function buildFirebaseClient(array $credentials): Client
    {
        $client = new Client();
        $client->setAuthConfig($credentials);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        return $client;
    }
}

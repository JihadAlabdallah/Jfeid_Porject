<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;

/**
 * Sends Firebase Cloud Messaging notifications to a topic.
 * Uses the same FIREBASE_CREDENTIALS (base64 service-account JSON) as the
 * admin "Send Notification" action.
 */
class FcmService
{
    public function isConfigured(): bool
    {
        return (string) config('services.firebase.credentials') !== '';
    }

    /**
     * @return array{ok: bool, response: mixed}
     */
    public function sendToTopic(string $topic, string $title, string $body): array
    {
        $credentials = $this->credentials();

        $client = new GoogleClient();
        $client->setAuthConfig($credentials);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'] ?? null;
        if (! is_string($accessToken) || $accessToken === '') {
            throw new \RuntimeException('Failed to fetch Firebase access token.');
        }

        $projectId = (string) ($credentials['project_id'] ?? '');
        if ($projectId === '') {
            throw new \RuntimeException('Firebase project_id is missing in FIREBASE_CREDENTIALS.');
        }

        $response = Http::withToken($accessToken)->post(
            "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
            [
                'message' => [
                    'topic' => $topic,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'message' => $body,
                    ],
                ],
            ]
        );

        return ['ok' => $response->successful(), 'response' => $response->json()];
    }

    private function credentials(): array
    {
        $decoded = base64_decode((string) config('services.firebase.credentials'), true);
        $credentials = $decoded === false ? null : json_decode($decoded, true);

        if (! is_array($credentials)) {
            throw new \RuntimeException('FIREBASE_CREDENTIALS must be base64-encoded service account JSON.');
        }

        return $credentials;
    }
}

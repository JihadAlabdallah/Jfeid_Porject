<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;

/**
 * Sends Firebase Cloud Messaging notifications to a topic or to one phone
 * (device token). Uses FIREBASE_CREDENTIALS (base64 service-account JSON).
 */
class FcmService
{
    private ?string $accessToken = null;

    public function isConfigured(): bool
    {
        return (string) config('services.firebase.credentials') !== '';
    }

    /**
     * @return array{ok: bool, response: mixed}
     */
    public function sendToTopic(string $topic, string $title, string $body): array
    {
        return $this->send(['topic' => $topic], $title, $body);
    }

    /**
     * One phone. "unregistered" is true when Firebase says the token no longer
     * exists (app removed / reinstalled), so the caller can forget it.
     *
     * @return array{ok: bool, response: mixed, unregistered: bool}
     */
    public function sendToToken(string $token, string $title, string $body): array
    {
        $result = $this->send(['token' => $token], $title, $body);
        $result['unregistered'] = ! $result['ok'] && $this->isUnregistered($result['response']);

        return $result;
    }

    /**
     * @param  array<string, string>  $target  ['topic' => ...] or ['token' => ...]
     * @return array{ok: bool, response: mixed}
     */
    protected function send(array $target, string $title, string $body): array
    {
        $response = Http::withToken($this->accessToken())->post(
            "https://fcm.googleapis.com/v1/projects/{$this->projectId()}/messages:send",
            [
                'message' => $target + [
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

    /** Google access token, fetched once per send batch. */
    protected function accessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $client = new GoogleClient();
        $client->setAuthConfig($this->credentials());
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $token = $client->fetchAccessTokenWithAssertion()['access_token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('Failed to fetch Firebase access token.');
        }

        return $this->accessToken = $token;
    }

    protected function projectId(): string
    {
        $projectId = (string) ($this->credentials()['project_id'] ?? '');
        if ($projectId === '') {
            throw new \RuntimeException('Firebase project_id is missing in FIREBASE_CREDENTIALS.');
        }

        return $projectId;
    }

    private function isUnregistered(mixed $response): bool
    {
        foreach ((array) data_get($response, 'error.details', []) as $detail) {
            if (in_array(data_get($detail, 'errorCode'), ['UNREGISTERED', 'SENDER_ID_MISMATCH'], true)) {
                return true;
            }
        }

        return data_get($response, 'error.status') === 'NOT_FOUND';
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

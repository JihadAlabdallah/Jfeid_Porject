<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Push notifications from the dashboard: to every customer (Firebase topic
 * all_users, which every installed app is subscribed to) or to chosen
 * customers (each of their phones' device tokens). Every send is logged in
 * the notifications table.
 */
class NotificationSender
{
    public const ALL_USERS_TOPIC = 'all_users';

    public function __construct(private FcmService $fcm)
    {
    }

    public function toAll(string $title, string $body): Notification
    {
        $this->ensureConfigured();

        $result = $this->fcm->sendToTopic(self::ALL_USERS_TOPIC, $title, $body);

        return Notification::create([
            'topic' => self::ALL_USERS_TOPIC,
            'audience' => 'all',
            'title' => $title,
            'body' => $body,
            'response' => $result['response'],
            'sent_count' => $result['ok'] ? null : 0,
            'failed_count' => $result['ok'] ? 0 : 1,
        ]);
    }

    /**
     * @param  Collection<int, User>|array<int, int>  $users  users or user ids
     */
    public function toUsers(Collection|array $users, string $title, string $body): Notification
    {
        $this->ensureConfigured();

        $ids = collect($users)->map(fn ($u) => $u instanceof User ? $u->id : (int) $u)->unique()->values();
        $tokens = DeviceToken::whereIn('user_id', $ids)->get();

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($tokens as $device) {
            $result = $this->fcm->sendToToken($device->token, $title, $body);

            if ($result['ok']) {
                // last_used_at is left alone: it means "app last opened" (set when the app registers)
                $sent++;
                continue;
            }

            $failed++;
            $errors[] = ['user_id' => $device->user_id, 'error' => data_get($result['response'], 'error.status', 'error')];

            // phone no longer has the app: stop sending to it
            if ($result['unregistered']) {
                $device->delete();
            }
        }

        $reached = $tokens->pluck('user_id')->unique()->count();

        return Notification::create([
            'topic' => 'selected_users',
            'audience' => 'selected',
            'recipients' => $ids->all(),
            'title' => $title,
            'body' => $body,
            'response' => [
                'customers' => $ids->count(),
                'customers_without_app' => $ids->count() - $reached,
                'errors' => $errors,
            ],
            'sent_count' => $sent,
            'failed_count' => $failed,
        ]);
    }

    private function ensureConfigured(): void
    {
        if (! $this->fcm->isConfigured()) {
            throw new \RuntimeException('Push notifications are not set up on this server (FIREBASE_CREDENTIALS is empty).');
        }
    }
}

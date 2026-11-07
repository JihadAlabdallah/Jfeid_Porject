<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Services\FcmService;
use Illuminate\Console\Command;

class SendWeeklyReminder extends Command
{
    public const TOPIC = 'all_users';
    public const TITLE = 'Time to check your car';
    public const BODY = 'Time to check your car! Open the app to see your maintenance schedule.';

    protected $signature = 'notifications:weekly-reminder {--force : Send even if one was sent in the last 6 days}';

    protected $description = 'Send the weekly "Time to check your car" push notification to all users';

    public function handle(FcmService $fcm): int
    {
        // guard against double sends (e.g. a redeploy right after the scheduled run)
        $alreadySent = Notification::where('topic', self::TOPIC)
            ->where('title', self::TITLE)
            ->where('created_at', '>=', now()->subDays(6))
            ->exists();

        if ($alreadySent && ! $this->option('force')) {
            $this->info('Weekly reminder already sent in the last 6 days; skipping.');
            return self::SUCCESS;
        }

        if (! $fcm->isConfigured()) {
            $this->warn('FIREBASE_CREDENTIALS is not set; nothing sent.');
            return self::SUCCESS;
        }

        $result = $fcm->sendToTopic(self::TOPIC, self::TITLE, self::BODY);

        Notification::create([
            'topic' => self::TOPIC,
            'title' => self::TITLE,
            'body' => self::BODY,
            'response' => $result['response'],
        ]);

        if (! $result['ok']) {
            $this->error('FCM rejected the notification: ' . json_encode($result['response']));
            return self::FAILURE;
        }

        $this->info('Weekly reminder sent.');
        return self::SUCCESS;
    }
}

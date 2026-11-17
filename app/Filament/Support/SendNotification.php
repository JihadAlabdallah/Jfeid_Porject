<?php

namespace App\Filament\Support;

use App\Models\Notification as NotificationLog;
use App\Models\User;
use App\Services\NotificationSender;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;

/**
 * The "Send notification" form and action, used on the Notifications page
 * (all customers or chosen ones) and by the Users bulk action (the selected rows).
 */
class SendNotification
{
    public const TITLE_MAX = 100;

    public const BODY_MAX = 255;

    /** Title + message fields. */
    public static function messageFields(): array
    {
        return [
            TextInput::make('title')
                ->label('Title')
                ->required()
                ->maxLength(self::TITLE_MAX)
                ->placeholder('e.g. Time to check your car'),
            Textarea::make('body')
                ->label('Message')
                ->required()
                ->maxLength(self::BODY_MAX)
                ->rows(3)
                ->placeholder('e.g. Check your car and oil levels today.'),
        ];
    }

    /** Full form for the Notifications page. */
    public static function form(): array
    {
        return [
            Radio::make('audience')
                ->label('Send to')
                ->options([
                    'all' => 'All customers',
                    'selected' => 'Selected customers',
                ])
                ->default('all')
                ->required()
                ->live(),
            Select::make('users')
                ->label('Customers')
                ->multiple()
                ->searchable()
                ->preload()
                ->options(fn () => self::customerOptions())
                ->visible(fn (Get $get) => $get('audience') === 'selected')
                ->required(fn (Get $get) => $get('audience') === 'selected')
                ->live()
                ->helperText('Only customers who signed in on the new app version can receive. Others are marked "no app".'),
            Placeholder::make('reach')
                ->hiddenLabel()
                ->visible(fn (Get $get) => $get('audience') === 'selected' && filled($get('users')))
                ->content(fn (Get $get) => self::reachText((array) $get('users'))),
            ...self::messageFields(),
        ];
    }

    /** @return array<int, string> "Name (phone)" per customer, flagged when they can't receive */
    public static function customerOptions(): array
    {
        return User::query()
            ->withCount('deviceTokens')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $u) => [
                $u->id => trim($u->name . ($u->phonenumber ? " ({$u->phonenumber})" : ''))
                    . ($u->device_tokens_count > 0 ? '' : ' - no app'),
            ])
            ->all();
    }

    public static function reachText(array $userIds): string
    {
        $total = count($userIds);
        $reachable = User::whereIn('id', $userIds)->whereHas('deviceTokens')->count();

        return "{$reachable} of {$total} selected customers can receive it now.";
    }

    /**
     * Send and show the result as a dashboard message.
     *
     * @param  array<int, int>|null  $userIds  null = all customers
     */
    public static function send(?array $userIds, string $title, string $body): ?NotificationLog
    {
        try {
            $sender = app(NotificationSender::class);
            $log = $userIds === null ? $sender->toAll($title, $body) : $sender->toUsers($userIds, $title, $body);
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('Notification not sent')->body($e->getMessage())->danger()->send();

            return null;
        }

        if ($log->audience === 'all') {
            $ok = $log->failed_count === 0;
            Notification::make()
                ->title($ok ? 'Sent to all customers' : 'Firebase refused the notification')
                ->body($ok ? null : 'Nothing was sent. See the Notifications list for the error.')
                ->{$ok ? 'success' : 'danger'}()
                ->send();

            return $log;
        }

        $without = (int) data_get($log->response, 'customers_without_app', 0);
        $lines = ["Delivered to {$log->sent_count} phone(s)."];
        if ($log->failed_count > 0) {
            $lines[] = "{$log->failed_count} phone(s) failed.";
        }
        if ($without > 0) {
            $lines[] = "{$without} customer(s) don't have the new app yet, so they didn't get it.";
        }

        Notification::make()
            ->title($log->sent_count > 0 ? 'Notification sent' : 'Nobody received it')
            ->body(implode(' ', $lines))
            ->{$log->sent_count > 0 ? 'success' : 'warning'}()
            ->send();

        return $log;
    }
}

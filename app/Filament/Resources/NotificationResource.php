<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Push notifications: send to all / chosen customers, and the history of
 * everything sent (including the weekly Monday reminder).
 */
class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationGroup = 'Admin';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $modelLabel = 'notification';

    public static string $panel = 'jfeid';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent at')
                    ->dateTime('M j, Y H:i')
                    ->timezone('Asia/Beirut')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('body')
                    ->label('Message')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->body)
                    ->wrap(),
                Tables\Columns\TextColumn::make('audience')
                    ->label('Sent to')
                    ->badge()
                    ->getStateUsing(fn ($record) => self::audienceLabel($record))
                    ->color(fn ($record) => $record->audience === 'selected' ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('result')
                    ->label('Result')
                    ->badge()
                    ->getStateUsing(fn ($record) => self::result($record))
                    ->color(fn ($record) => self::resultColor($record)),
            ]);
    }

    public static function audienceLabel(Notification $n): string
    {
        if ($n->audience === 'selected') {
            return count((array) $n->recipients) . ' selected';
        }

        return $n->topic === 'all_users' ? 'All customers' : ($n->topic ?: '-');
    }

    public static function result(Notification $n): string
    {
        if ($n->audience === 'selected') {
            $text = "{$n->sent_count} delivered";
            if ($n->failed_count) {
                $text .= ", {$n->failed_count} failed";
            }
            $without = (int) data_get($n->response, 'customers_without_app', 0);

            return $without ? "{$text}, {$without} no app" : $text;
        }

        // topic sends (incl. old rows): Firebase answers with a message name when accepted
        if (data_get(self::response($n), 'name')) {
            return 'Sent';
        }

        return data_get(self::response($n), 'error') ? 'Failed' : 'Unknown';
    }

    public static function resultColor(Notification $n): string
    {
        if ($n->audience === 'selected') {
            return $n->sent_count > 0 ? ($n->failed_count ? 'warning' : 'success') : 'danger';
        }

        return data_get(self::response($n), 'name') ? 'success' : 'danger';
    }

    /** Firebase's answer; the old /send-notification endpoint saved it as JSON text. */
    private static function response(Notification $n): mixed
    {
        $response = $n->response;

        return is_string($response) ? json_decode($response, true) : $response;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
        ];
    }
}

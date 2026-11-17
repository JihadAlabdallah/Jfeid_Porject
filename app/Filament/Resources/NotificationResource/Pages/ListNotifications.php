<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use App\Filament\Support\SendNotification;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Send notification')
                ->icon('heroicon-o-paper-airplane')
                ->modalHeading('Send a notification')
                ->modalSubmitActionLabel('Send')
                ->form(SendNotification::form())
                ->action(function (array $data) {
                    SendNotification::send(
                        $data['audience'] === 'selected' ? array_map('intval', (array) $data['users']) : null,
                        $data['title'],
                        $data['body'],
                    );
                }),
        ];
    }
}

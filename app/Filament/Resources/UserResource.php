<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\BulkAction;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Support\SendNotification;
use Filament\Forms\Components\{TextInput, Toggle};
use Filament\Tables\Columns\{TextColumn, IconColumn};

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Admin';
    public static string $panel = 'jfeid';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->email()
                ->nullable()
                ->unique(ignoreRecord: true),
            TextInput::make('phonenumber')
                ->label('Phone Number')
                ->nullable()
                ->maxLength(15),
            TextInput::make('password')
                ->password()
                ->required(fn(string $context) => $context === 'create')
                ->maxLength(255)
                ->dehydrateStateUsing(fn($state) => bcrypt($state)),
            TextInput::make('carplate')
                ->label('Car Plate')
                ->nullable()
                ->maxLength(20),
            Toggle::make('is_admin')
                ->label('Admin Privileges'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->visible(fn() => Auth::user()?->is_admin)
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ]),
                TextColumn::make('carplate')->label('Car Plate'),
                TextColumn::make('phonenumber')->label('Phone Number'),
                // has a phone registered for push notifications (new app version)
                IconColumn::make('device_tokens_exists')
                    ->label('App')
                    ->getStateUsing(fn (User $record) => $record->deviceTokens()->exists())
                    ->boolean()
                    ->tooltip(fn ($state) => $state ? 'Can receive notifications' : 'No phone registered yet'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->bulkActions([
                // sends only to the selected customers' phones (it used to go to everyone)
                BulkAction::make('send-notification')
                    ->label('Send Notification')
                    ->icon('heroicon-o-paper-airplane')
                    ->modalHeading('Send a notification to the selected customers')
                    ->modalDescription(fn (Collection $records) => SendNotification::reachText($records->modelKeys()))
                    ->modalSubmitActionLabel('Send')
                    ->form(SendNotification::messageFields())
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (Collection $records, array $data) => SendNotification::send(
                        $records->modelKeys(),
                        $data['title'],
                        $data['body'],
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

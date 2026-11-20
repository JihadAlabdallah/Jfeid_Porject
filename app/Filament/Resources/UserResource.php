<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
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
            ->modifyQueryUsing(fn (Builder $query) => $query->withAppActivity())
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
                    ->getStateUsing(fn (User $record) => (bool) ($record->device_tokens_exists ?? $record->deviceTokens()->exists()))
                    ->boolean()
                    ->tooltip(fn (User $record, $state) => match (true) {
                        ! $state => 'No phone registered yet (needs the new app version)',
                        (bool) $record->app_last_opened => 'Can receive notifications. App last opened '
                            . Carbon::parse($record->app_last_opened)->timezone('Asia/Beirut')->format('M j, Y H:i'),
                        default => 'Can receive notifications',
                    }),
                // last sign-in or signed-in activity in the app (see User::scopeWithAppActivity)
                TextColumn::make('last_seen_in_app')
                    ->label('Last in app')
                    ->getStateUsing(fn (User $record) => $record->lastSeenInApp())
                    ->since()
                    ->placeholder('Never')
                    ->tooltip(fn (User $record) => $record->lastSeenInApp()
                        ? $record->lastSeenInApp()->timezone('Asia/Beirut')->format('M j, Y H:i')
                            . ' (last sign-in or account activity in the app)'
                        : 'Never signed in to the app, or logged out'),
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

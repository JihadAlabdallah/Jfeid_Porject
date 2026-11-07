<?php

namespace App\Filament\Resources;

use App\Models\WheelService;
use App\Filament\Resources\WheelServiceResource\Pages;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
// use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder;

class WheelServiceResource extends Resource
{
    protected static ?string $model = WheelService::class;
    protected static ?string $navigationGroup = 'Maintenance';
    protected static ?string $navigationLabel = 'Wheel Services';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['maintenanceRecord.vehicle.user', 'maintenanceRecord']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('record_id')
                    ->relationship('maintenanceRecord', 'id')
                    ->label('Maintenance Record')
                    ->required(),
                TextInput::make('wheel_name')
                    ->label('Wheel Name')
                    ->placeholder('Enter wheel name')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('wheel_size')
                    ->label('Wheel Size')
                    ->placeholder('Enter wheel size')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('quantity')
                    ->label('Quantity')
                    ->placeholder('Enter quantity')
                    ->numeric()
                    ->required(),
                ...\App\Filament\Support\ParentRecordFields::formFields(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('maintenanceRecord.vehicle.user.name')
                    ->label('User Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('maintenanceRecord.vehicle.type')
                    ->label('Vehicle Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('maintenanceRecord.type')
                    ->label('Maintenance Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('wheel_name')
                    ->label('Wheel Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('wheel_size')
                    ->label('Wheel Size')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('maintenanceRecord.maintenance_date')
                    ->dateTime()
                    ->label('Maintenance Date')
                    ->sortable(),
                ...\App\Filament\Support\ParentRecordFields::tableColumns(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWheelServices::route('/'),
            'create' => Pages\CreateWheelService::route('/create'),
            'edit' => Pages\EditWheelService::route('/{record}/edit'),
        ];
    }
}

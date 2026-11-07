<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OtherServiceResource\Pages;
use App\Filament\Support\MaintenanceFlow;
use App\Filament\Support\ParentRecordFields;
use App\Models\OtherService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Other" services: anything outside the fixed maintenance types.
 * New ones are created from Maintenance Records -> New (type "Other").
 */
class OtherServiceResource extends Resource
{
    protected static ?string $model = OtherService::class;
    protected static ?string $navigationGroup = 'Maintenance';
    protected static ?string $navigationLabel = 'Other Services';
    protected static ?string $modelLabel = 'Other Service';
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['maintenanceRecord.vehicle.user']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                MaintenanceFlow::recordField(),
                Forms\Components\TextInput::make('name')
                    ->label('Service name')
                    ->maxLength(255)
                    ->required(),
                Forms\Components\TextInput::make('category')
                    ->label('Type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->minValue(0),
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->maxLength(5000)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('note')
                    ->label('Note')
                    ->rows(2)
                    ->maxLength(5000)
                    ->columnSpanFull(),
                ...ParentRecordFields::formFields(),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Type')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->placeholder('-')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->placeholder('-')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('maintenanceRecord.maintenance_date')
                    ->dateTime()
                    ->label('Maintenance Date')
                    ->sortable(),
                ...ParentRecordFields::tableColumns(),
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
            'index' => Pages\ListOtherServices::route('/'),
            'create' => Pages\CreateOtherService::route('/create'),
            'edit' => Pages\EditOtherService::route('/{record}/edit'),
        ];
    }
}

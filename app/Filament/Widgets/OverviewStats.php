<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\MaintenanceRecord;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class OverviewStats extends BaseWidget
{
    protected static string $view = 'filament.widgets.overview-stats';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function getStats(): array
    {
        return [
            [
                'title' => 'Users',
                'count' => User::count(),
                'view_route' => route('filament.jfeid.resources.users.index'),
                'create_route' => route('filament.jfeid.resources.users.create'),
            ],
            [
                'title' => 'Vehicles',
                'count' => Vehicle::count(),
                'view_route' => route('filament.jfeid.resources.vehicles.index'),
                'create_route' => route('filament.jfeid.resources.vehicles.create'),
            ],
            [
                'title' => 'Maintenance Records',
                'count' => MaintenanceRecord::count(),
                'view_route' => route('filament.jfeid.resources.maintenance-records.index'),
                'create_route' => route('filament.jfeid.resources.maintenance-records.create'),
            ],
        ];
    }
}

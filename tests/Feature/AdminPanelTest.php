<?php

namespace Tests\Feature;

use App\Filament\Resources\MaintenanceRecordResource\Pages\CreateMaintenanceRecord;
use App\Filament\Resources\MaintenanceRecordResource\Pages\EditMaintenanceRecord;
use App\Filament\Widgets\MonthlyRevenueChart;
use App\Filament\Widgets\RevenueStats;
use App\Models\MaintenanceRecord;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'phonenumber' => '000',
            'password' => 'secret123',
            'is_admin' => true,
        ]);

        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('jfeid'));
    }

    public function test_dashboard_loads(): void
    {
        $this->get('/Jfeid')->assertOk();
    }

    public function test_revenue_widgets_render_with_prices(): void
    {
        $vehicle = Vehicle::create(['user_id' => $this->admin->id, 'type' => 'Hilux']);
        MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'maintenance_date' => now(),
            'type' => 'oil_change',
            'price' => 123.45,
        ]);

        Livewire::test(RevenueStats::class)
            ->assertSee('Revenue this month')
            ->assertSee('$123.45')
            ->assertSee('Revenue last 6 months')
            ->assertSee('Revenue this year');

        Livewire::test(MonthlyRevenueChart::class)->assertOk();
    }

    public function test_admin_can_create_record_with_technician_and_price(): void
    {
        $vehicle = Vehicle::create(['user_id' => $this->admin->id, 'type' => 'Hilux']);

        Livewire::test(CreateMaintenanceRecord::class)
            ->assertSee('Price (USD)')
            ->assertSee('Technician / Employee')
            ->fillForm([
                'user_id' => $this->admin->id,
                'vehicle_id' => $vehicle->id,
                'type' => 'battery_service',
                'brand' => 'Bosch',
                'size' => '70Ah',
                'maintenance_date' => now()->toDateTimeString(),
                'technician_name' => 'Ahmed',
                'price' => '89.99',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $record = MaintenanceRecord::first();
        $this->assertSame('Ahmed', $record->technician_name);
        $this->assertSame(89.99, $record->price);
        $this->assertNotNull($record->batteryService);
    }

    public function test_price_and_technician_are_optional(): void
    {
        $vehicle = Vehicle::create(['user_id' => $this->admin->id, 'type' => 'Hilux']);

        Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm([
                'user_id' => $this->admin->id,
                'vehicle_id' => $vehicle->id,
                'type' => 'battery_service',
                'brand' => 'Bosch',
                'size' => '70Ah',
                'maintenance_date' => now()->toDateTimeString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNull(MaintenanceRecord::first()->price);
    }

    public function test_negative_price_is_rejected(): void
    {
        $vehicle = Vehicle::create(['user_id' => $this->admin->id, 'type' => 'Hilux']);

        Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm([
                'user_id' => $this->admin->id,
                'vehicle_id' => $vehicle->id,
                'type' => 'battery_service',
                'brand' => 'Bosch',
                'size' => '70Ah',
                'maintenance_date' => now()->toDateTimeString(),
                'price' => '-5',
            ])
            ->call('create')
            ->assertHasFormErrors(['price']);
    }

    public function test_editing_an_old_record_adds_a_price(): void
    {
        $vehicle = Vehicle::create(['user_id' => $this->admin->id, 'type' => 'Hilux']);
        $record = MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'maintenance_date' => now(),
            'type' => 'battery_service',
        ]);
        $record->batteryService()->create(['brand' => 'Bosch', 'size' => '70Ah']);

        Livewire::test(EditMaintenanceRecord::class, ['record' => $record->getRouteKey()])
            ->fillForm(['price' => '40', 'technician_name' => 'Sami'])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $record->fresh();
        $this->assertSame(40.0, $fresh->price);
        $this->assertSame('Sami', $fresh->technician_name);
        $this->assertSame('Bosch', $fresh->batteryService->brand);
    }
}

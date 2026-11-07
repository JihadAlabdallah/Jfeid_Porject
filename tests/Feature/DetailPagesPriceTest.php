<?php

namespace Tests\Feature;

use App\Filament\Resources;
use App\Filament\Resources\OilChangeResource\Pages\CreateOilChange;
use App\Filament\Resources\OilChangeResource\Pages\EditOilChange;
use App\Models\MaintenanceRecord;
use App\Models\OilChange;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DetailPagesPriceTest extends TestCase
{
    use RefreshDatabase;

    private MaintenanceRecord $record;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'phonenumber' => '000',
            'password' => 'secret123',
            'is_admin' => true,
        ]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('jfeid'));

        $vehicle = Vehicle::create(['user_id' => $admin->id, 'type' => 'Hilux']);
        $this->record = MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'maintenance_date' => now(),
            'type' => 'oil_change',
        ]);
    }

    private function oilFields(array $extra = []): array
    {
        return $extra + [
            'record_id' => $this->record->id,
            'oil_type' => '5W-30',
            'oil_quantity' => 4,
            'current_km' => 50000,
            'next_change_km' => 55000,
            'filter' => 'OEM',
        ];
    }

    public function test_creating_an_oil_change_saves_price_and_technician_on_the_record(): void
    {
        Livewire::test(CreateOilChange::class)
            ->assertSee('Price (USD)')
            ->fillForm($this->oilFields(['technician_name' => 'Ahmed', 'price' => '60']))
            ->call('create')
            ->assertHasNoFormErrors();

        $fresh = $this->record->fresh();
        $this->assertSame('Ahmed', $fresh->technician_name);
        $this->assertSame(60.0, $fresh->price);
        $this->assertSame(1, OilChange::count());
    }

    public function test_creating_without_price_does_not_wipe_an_existing_price(): void
    {
        $this->record->update(['price' => 99, 'technician_name' => 'Sami']);

        Livewire::test(CreateOilChange::class)
            ->fillForm($this->oilFields())
            ->call('create')
            ->assertHasNoFormErrors();

        $fresh = $this->record->fresh();
        $this->assertSame(99.0, $fresh->price);
        $this->assertSame('Sami', $fresh->technician_name);
    }

    public function test_edit_page_shows_and_updates_the_records_price(): void
    {
        $this->record->update(['price' => 30, 'technician_name' => 'Sami']);
        $oil = OilChange::create($this->oilFields());

        Livewire::test(EditOilChange::class, ['record' => $oil->getRouteKey()])
            ->assertFormSet(['price' => 30.0, 'technician_name' => 'Sami'])
            ->fillForm(['price' => '45.5'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(45.5, $this->record->fresh()->price);
        $this->assertSame('Sami', $this->record->fresh()->technician_name);
    }

    public function test_negative_price_is_rejected(): void
    {
        Livewire::test(CreateOilChange::class)
            ->fillForm($this->oilFields(['price' => '-1']))
            ->call('create')
            ->assertHasFormErrors(['price']);
    }

    public function test_every_detail_list_and_create_page_loads(): void
    {
        $resources = [
            Resources\OilChangeResource::class,
            Resources\SteeringOilChangeResource::class,
            Resources\BatteryServiceResource::class,
            Resources\WheelServiceResource::class,
            Resources\TransmissionOilChangeResource::class,
            Resources\DifferentialOilChangeResource::class,
            Resources\GearOilChangeResource::class,
        ];

        OilChange::create($this->oilFields());
        $this->record->update(['price' => 12.5, 'technician_name' => 'Ahmed']);

        foreach ($resources as $resource) {
            $this->get($resource::getUrl('index'))->assertOk();
            $this->get($resource::getUrl('create'))->assertOk()->assertSee('Price (USD)');
        }

        $this->get(Resources\OilChangeResource::getUrl('index'))
            ->assertSee('Ahmed')
            ->assertSee('$12.50');
    }
}

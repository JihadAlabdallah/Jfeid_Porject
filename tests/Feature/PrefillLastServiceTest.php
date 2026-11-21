<?php

namespace Tests\Feature;

use App\Filament\Resources\MaintenanceRecordResource\Pages\CreateMaintenanceRecord;
use App\Filament\Resources\MaintenanceRecordResource\Pages\EditMaintenanceRecord;
use App\Filament\Resources\MaintenanceRecordsRelationResource\RelationManagers\MaintenanceRecordsRelationManager;
use App\Filament\Resources\VehicleResource\Pages\EditVehicle;
use App\Models\MaintenanceRecord;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** A new service record starts with the details of the car's last service of that type. */
class PrefillLastServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Vehicle $hilux;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'phonenumber' => '0', 'password' => 'secret123', 'is_admin' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('jfeid'));

        $this->customer = User::create(['name' => 'Maryam', 'email' => 'maryam@example.com', 'phonenumber' => '1', 'password' => 'secret123']);
        $this->hilux = Vehicle::create(['user_id' => $this->customer->id, 'type' => 'Hilux', 'distance_unit' => 'km']);
    }

    private function service(Vehicle $car, string $type, string $date, array $details, array $record = []): MaintenanceRecord
    {
        $r = MaintenanceRecord::create($record + ['vehicle_id' => $car->id, 'type' => $type, 'maintenance_date' => $date]);
        $r->syncTypeDetails($details);

        return $r;
    }

    private function oil(Vehicle $car, string $date, string $oilType, string $qty, string $filter): MaintenanceRecord
    {
        return $this->service($car, 'oil_change', $date, ['oil_type' => $oilType, 'oil_quantity' => $qty, 'filter' => $filter, 'current_km' => '50000'], ['price' => 40, 'technician_name' => 'Sami']);
    }

    public function test_new_oil_change_is_filled_from_the_cars_latest_oil_change(): void
    {
        $this->oil($this->hilux, '2026-05-01 10:00:00', '10W-40', '5', 'Old filter');
        $this->oil($this->hilux, '2026-10-01 10:00:00', '5W-30', '6', 'Toyota OEM'); // latest

        Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm(['user_id' => $this->customer->id])
            ->fillForm(['vehicle_id' => $this->hilux->id])
            ->fillForm(['type' => 'oil_change'])
            ->assertFormSet(['oil_type' => '5W-30', 'oil_quantity' => '6', 'filter' => 'Toyota OEM'])
            // readings, price and technician are NOT copied
            ->assertFormSet(['current_km' => null, 'price' => null, 'technician_name' => null])
            ->assertSee('Details filled in from this car’s last Oil Change (Oct 1, 2026)', false);
    }

    public function test_filled_details_can_be_changed_and_are_saved_as_typed(): void
    {
        $this->oil($this->hilux, '2026-10-01 10:00:00', '5W-30', '6', 'Toyota OEM');

        Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm(['user_id' => $this->customer->id])
            ->fillForm(['vehicle_id' => $this->hilux->id])
            ->fillForm(['type' => 'oil_change'])
            ->fillForm(['oil_quantity' => '7', 'current_km' => '60000', 'maintenance_date' => '2026-11-21 10:00:00'])
            ->call('create')
            ->assertHasNoFormErrors();

        $new = MaintenanceRecord::latest('id')->first();
        $this->assertSame('5W-30', $new->oilChange->oil_type);
        $this->assertSame('7', (string) $new->oilChange->oil_quantity);
        $this->assertSame('Toyota OEM', $new->oilChange->filter);
        $this->assertSame('60000', (string) $new->oilChange->current_km);
        // the old record is untouched
        $this->assertSame('6', (string) MaintenanceRecord::oldest('id')->first()->oilChange->oil_quantity);
    }

    public function test_type_chosen_before_the_car_also_fills_in(): void
    {
        $this->service($this->hilux, 'battery_service', '2026-09-01 10:00:00', ['brand' => 'Bosch', 'size' => '70Ah']);

        Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm(['type' => 'battery_service'])
            ->fillForm(['user_id' => $this->customer->id])
            ->fillForm(['vehicle_id' => $this->hilux->id])
            ->assertFormSet(['brand' => 'Bosch', 'size' => '70Ah']);
    }

    public function test_only_this_cars_and_this_types_history_is_used(): void
    {
        $corolla = Vehicle::create(['user_id' => $this->customer->id, 'type' => 'Corolla', 'distance_unit' => 'km']);
        $this->oil($corolla, '2026-10-01 10:00:00', '0W-20', '4', 'Corolla filter');
        $this->service($this->hilux, 'gear_oil_change', '2026-10-01 10:00:00', ['oil_type' => '75W-90', 'quantity' => '2']);

        $form = Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm(['user_id' => $this->customer->id])
            ->fillForm(['vehicle_id' => $corolla->id])
            ->fillForm(['type' => 'oil_change'])
            ->assertFormSet(['oil_type' => '0W-20']);

        // switching to the Hilux, which never had an oil change: nothing from the Corolla stays
        $form->fillForm(['vehicle_id' => $this->hilux->id])
            ->assertFormSet(['oil_type' => null, 'oil_quantity' => null, 'filter' => null])
            ->assertDontSee('Details filled in from');

        // a different type uses that type's history
        $form->fillForm(['type' => 'gear_oil_change'])
            ->assertFormSet(['oil_type' => '75W-90', 'quantity' => '2']);
    }

    public function test_car_page_new_record_is_filled_in_too(): void
    {
        $this->service($this->hilux, 'wheel_service', '2026-08-01 10:00:00', ['wheel_name' => 'Michelin', 'quantity' => '4', 'wheel_size' => '265/65R17']);

        Livewire::test(MaintenanceRecordsRelationManager::class, ['ownerRecord' => $this->hilux, 'pageClass' => EditVehicle::class])
            ->mountTableAction('create')
            ->setTableActionData(['type' => 'wheel_service'])
            ->assertTableActionDataSet(['wheel_name' => 'Michelin', 'quantity' => '4', 'wheel_size' => '265/65R17']);
    }

    public function test_editing_an_existing_record_never_changes_its_details(): void
    {
        $this->oil($this->hilux, '2026-10-01 10:00:00', '5W-30', '6', 'Toyota OEM');
        $older = $this->oil($this->hilux, '2026-05-01 10:00:00', '10W-40', '5', 'Old filter');

        Livewire::test(EditMaintenanceRecord::class, ['record' => $older->getRouteKey()])
            ->assertFormSet(['oil_type' => '10W-40', 'oil_quantity' => '5'])
            ->fillForm(['type' => 'oil_change'])
            ->assertFormSet(['oil_type' => '10W-40', 'oil_quantity' => '5', 'filter' => 'Old filter'])
            ->assertDontSee('Details filled in from');

        Livewire::test(MaintenanceRecordsRelationManager::class, ['ownerRecord' => $this->hilux, 'pageClass' => EditVehicle::class])
            ->mountTableAction('edit', $older)
            ->setTableActionData(['type' => 'oil_change'])
            ->assertTableActionDataSet(['oil_type' => '10W-40', 'oil_quantity' => '5']);
    }

    public function test_other_services_are_not_copied(): void
    {
        $this->service($this->hilux, 'other', '2026-10-01 10:00:00', ['name' => 'Brake pads', 'quantity' => '2']);

        Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm(['user_id' => $this->customer->id])
            ->fillForm(['vehicle_id' => $this->hilux->id])
            ->fillForm(['type' => 'other'])
            ->assertFormSet(['name' => null]);
    }
}

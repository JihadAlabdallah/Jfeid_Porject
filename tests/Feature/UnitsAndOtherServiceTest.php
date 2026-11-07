<?php

namespace Tests\Feature;

use App\Filament\Resources\MaintenanceRecordResource\Pages\CreateMaintenanceRecord;
use App\Filament\Resources\MaintenanceRecordResource\Pages\EditMaintenanceRecord;
use App\Filament\Resources\MaintenanceRecordsRelationResource\RelationManagers\MaintenanceRecordsRelationManager;
use App\Filament\Resources\OilChangeResource;
use App\Filament\Resources\OtherServiceResource;
use App\Filament\Resources\VehicleResource\Pages\EditVehicle;
use App\Models\MaintenanceRecord;
use App\Models\OilChange;
use App\Models\OtherService;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * KM / miles per car, automatic next oil change, and the "Other" type.
 */
class UnitsAndOtherServiceTest extends TestCase
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
    }

    private function vehicle(string $unit = 'km', ?User $owner = null): Vehicle
    {
        return Vehicle::create([
            'user_id' => ($owner ?? $this->admin)->id,
            'type' => 'Hilux',
            'distance_unit' => $unit,
        ]);
    }

    private function record(Vehicle $vehicle, string $type = 'oil_change'): MaintenanceRecord
    {
        return MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'maintenance_date' => '2026-11-08 10:00:00',
            'type' => $type,
        ]);
    }

    private function admin(): void
    {
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('jfeid'));
    }

    // ---- per-user setting is gone ----

    public function test_per_user_unit_setting_is_removed(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'distance_unit'));

        Sanctum::actingAs($this->admin);
        $this->postJson('/api/profile/update/x', ['phonenumber' => '0599', 'distance_unit' => 'miles'])
            ->assertOk()
            ->assertJsonMissingPath('data.distance_unit');
    }

    // ---- unit per car ----

    public function test_cars_default_to_km(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/my-vehicles', ['type' => 'Corolla'])
            ->assertCreated()
            ->assertJsonPath('data.distance_unit', 'km');
    }

    public function test_customer_can_add_a_car_in_miles_and_switch_its_unit(): void
    {
        Sanctum::actingAs($this->admin);

        $id = $this->postJson('/api/my-vehicles', ['type' => 'Mustang', 'distance_unit' => 'miles'])
            ->assertCreated()
            ->assertJsonPath('data.distance_unit', 'miles')
            ->json('data.id');

        $this->patchJson("/api/my-vehicles/{$id}", ['distance_unit' => 'km'])
            ->assertOk()
            ->assertJsonPath('data.distance_unit', 'km')
            ->assertJsonPath('data.type', 'Mustang');
    }

    public function test_invalid_unit_is_rejected(): void
    {
        Sanctum::actingAs($this->admin);
        $vehicle = $this->vehicle();

        $this->patchJson("/api/my-vehicles/{$vehicle->id}", ['distance_unit' => 'm'])->assertUnprocessable();
        $this->postJson('/api/my-vehicles', ['type' => 'X', 'distance_unit' => 'meters'])->assertUnprocessable();
    }

    public function test_customer_cannot_change_someone_elses_car(): void
    {
        $other = User::create(['name' => 'Other', 'email' => 'o@example.com', 'phonenumber' => '1', 'password' => 'secret123']);
        $theirs = $this->vehicle('km', $other);

        Sanctum::actingAs($this->admin);
        $this->patchJson("/api/my-vehicles/{$theirs->id}", ['distance_unit' => 'miles'])->assertNotFound();

        $this->assertSame('km', $theirs->fresh()->distance_unit);
    }

    public function test_signin_and_history_include_the_cars_unit(): void
    {
        $vehicle = $this->vehicle('miles');

        $this->postJson('/api/signin', ['name_or_email' => 'admin@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('vehicles.0.distance_unit', 'miles');

        $this->getJson("/api/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.distance_unit', 'miles');
    }

    // ---- automatic next change + unit saved on the record ----

    public function test_next_change_is_current_plus_5000_km(): void
    {
        $oil = OilChange::create(['record_id' => $this->record($this->vehicle('km'))->id, 'current_km' => '52000']);

        $this->assertSame('57000', $oil->next_change_km);
        $this->assertSame('km', $oil->distance_unit);
    }

    public function test_next_change_is_current_plus_3107_miles(): void
    {
        $oil = OilChange::create(['record_id' => $this->record($this->vehicle('miles'))->id, 'current_km' => '40,000']);

        $this->assertSame('43107', $oil->next_change_km);
        $this->assertSame('miles', $oil->distance_unit);
    }

    public function test_a_next_change_that_is_typed_in_is_kept(): void
    {
        $oil = OilChange::create([
            'record_id' => $this->record($this->vehicle())->id,
            'current_km' => '52000',
            'next_change_km' => '55000',
        ]);

        $this->assertSame('55000', $oil->next_change_km);
    }

    public function test_non_numeric_reading_does_not_invent_a_next_change(): void
    {
        $oil = OilChange::create(['record_id' => $this->record($this->vehicle())->id, 'current_km' => 'unknown']);

        $this->assertNull($oil->next_change_km);
    }

    public function test_switching_a_cars_unit_keeps_old_records_in_their_unit(): void
    {
        $vehicle = $this->vehicle('km');
        $old = OilChange::create(['record_id' => $this->record($vehicle)->id, 'current_km' => '52000']);

        $vehicle->update(['distance_unit' => 'miles']);
        $new = OilChange::create(['record_id' => $this->record($vehicle)->id, 'current_km' => '33000']);

        $this->assertSame('km', $old->fresh()->distance_unit);
        $this->assertSame('57000', $old->fresh()->next_change_km);
        $this->assertSame('miles', $new->distance_unit);
        $this->assertSame('36107', $new->next_change_km);
    }

    public function test_app_api_oil_change_gets_unit_and_next_change(): void
    {
        $record = $this->record($this->vehicle('miles'));

        $this->postJson('/api/oil-changes', [
            'record_id' => $record->id,
            'current_km' => '10000',
            'oil_type' => '5W-30',
        ])->assertSuccessful()
            ->assertJsonPath('data.distance_unit', 'miles')
            ->assertJsonPath('data.next_change_km', '13107');
    }

    public function test_app_api_oil_change_update_now_saves(): void
    {
        $oil = OilChange::create(['record_id' => $this->record($this->vehicle())->id, 'current_km' => '52000']);

        $this->putJson("/api/oil-changes/{$oil->id}", ['oil_type' => '10W-40'])->assertOk();

        $this->assertSame('10W-40', $oil->fresh()->oil_type);
    }

    public function test_admin_form_fills_next_change_from_the_cars_unit(): void
    {
        $this->admin();
        $vehicle = $this->vehicle('miles');

        Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm([
                'user_id' => $this->admin->id,
                'vehicle_id' => $vehicle->id,
                'type' => 'oil_change',
                'oil_type' => '5W-30',
                'oil_quantity' => '4',
                'filter' => 'OEM',
                'maintenance_date' => now()->toDateTimeString(),
            ])
            ->fillForm(['current_km' => '40000'])
            ->assertFormSet(['next_change_km' => 43107])
            ->call('create')
            ->assertHasNoFormErrors();

        $oil = MaintenanceRecord::latest('id')->first()->oilChange;
        $this->assertSame('miles', $oil->distance_unit);
        $this->assertSame('43107', (string) $oil->next_change_km);
    }

    public function test_admin_can_leave_next_change_empty(): void
    {
        $this->admin();
        $vehicle = $this->vehicle('km');

        Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm([
                'user_id' => $this->admin->id,
                'vehicle_id' => $vehicle->id,
                'type' => 'oil_change',
                'oil_type' => '5W-30',
                'oil_quantity' => '4',
                'filter' => 'OEM',
                'current_km' => '52000',
                'next_change_km' => null,
                'maintenance_date' => now()->toDateTimeString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('57000', MaintenanceRecord::latest('id')->first()->oilChange->next_change_km);
    }

    public function test_car_page_form_uses_that_cars_unit(): void
    {
        $this->admin();
        $vehicle = $this->vehicle('miles');

        Livewire::test(MaintenanceRecordsRelationManager::class, [
            'ownerRecord' => $vehicle,
            'pageClass' => EditVehicle::class,
        ])->callTableAction('create', data: [
            'type' => 'oil_change',
            'maintenance_date' => now()->toDateTimeString(),
            'current_km' => '20000',
            'oil_type' => '5W-30',
            'oil_quantity' => '4',
            'filter' => 'OEM',
        ])->assertHasNoTableActionErrors();

        $oil = $vehicle->maintenanceRecords()->first()->oilChange;
        $this->assertSame('miles', $oil->distance_unit);
        $this->assertSame('23107', (string) $oil->next_change_km);
    }

    public function test_oil_changes_page_shows_readings_with_their_unit(): void
    {
        $this->admin();
        OilChange::create(['record_id' => $this->record($this->vehicle('miles'))->id, 'current_km' => '40000', 'oil_type' => 'x']);

        $this->get(OilChangeResource::getUrl('index'))
            ->assertOk()
            ->assertSee('40,000 mi')
            ->assertSee('43,107 mi');
    }

    // ---- "Other" type ----

    public function test_admin_creates_an_other_service_with_all_fields(): void
    {
        $this->admin();
        $vehicle = $this->vehicle();

        Livewire::withQueryParams(['type' => 'other'])
            ->test(CreateMaintenanceRecord::class)
            ->assertFormSet(['type' => 'other'])
            ->fillForm([
                'user_id' => $this->admin->id,
                'vehicle_id' => $vehicle->id,
                'maintenance_date' => now()->toDateTimeString(),
                'name' => 'Brake pads replacement',
                'category' => 'Brakes',
                'description' => 'Front pads',
                'note' => 'Customer supplied parts',
                'quantity' => '2',
                'technician_name' => 'Ahmed',
                'price' => '80',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $record = MaintenanceRecord::where('type', 'other')->first();
        $this->assertSame('Ahmed', $record->technician_name);
        $this->assertSame(80.0, $record->price);

        $other = $record->otherService;
        $this->assertSame('Brake pads replacement', $other->name);
        $this->assertSame('Brakes', $other->category);
        $this->assertSame('Front pads', $other->description);
        $this->assertSame('Customer supplied parts', $other->note);
        $this->assertSame(2.0, $other->quantity);
        $this->assertSame($record->id, (int) $other->record_id);
    }

    public function test_other_service_needs_only_a_name(): void
    {
        $this->admin();
        $vehicle = $this->vehicle();

        Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm([
                'user_id' => $this->admin->id,
                'vehicle_id' => $vehicle->id,
                'type' => 'other',
                'maintenance_date' => now()->toDateTimeString(),
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);

        Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm([
                'user_id' => $this->admin->id,
                'vehicle_id' => $vehicle->id,
                'type' => 'other',
                'maintenance_date' => now()->toDateTimeString(),
                'name' => 'Car wash',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $other = OtherService::first();
        $this->assertSame('Car wash', $other->name);
        $this->assertNull($other->category);
    }

    public function test_editing_an_other_record_loads_and_saves_its_details(): void
    {
        $this->admin();
        $record = $this->record($this->vehicle(), 'other');
        $record->otherService()->create(['name' => 'Car wash', 'category' => 'Cleaning']);

        Livewire::test(EditMaintenanceRecord::class, ['record' => $record->getRouteKey()])
            ->assertFormSet(['name' => 'Car wash', 'category' => 'Cleaning'])
            ->fillForm(['note' => 'Interior too'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Interior too', $record->fresh()->otherService->note);
        $this->assertSame(1, OtherService::count());
    }

    public function test_other_services_page_lists_and_new_goes_to_main_form(): void
    {
        $this->admin();
        $record = $this->record($this->vehicle(), 'other');
        $record->update(['price' => 15, 'technician_name' => 'Sami']);
        $record->otherService()->create(['name' => 'Car wash', 'category' => 'Cleaning']);

        $this->get(OtherServiceResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Car wash')
            ->assertSee('Cleaning')
            ->assertSee('$15.00');

        $this->get(OtherServiceResource::getUrl('create'))
            ->assertRedirect(\App\Filament\Resources\MaintenanceRecordResource::getUrl('create', ['type' => 'other']));
    }

    public function test_history_api_returns_other_service_with_price(): void
    {
        $vehicle = $this->vehicle();
        $record = $this->record($vehicle, 'other');
        $record->update(['price' => 15, 'technician_name' => 'Sami']);
        $record->otherService()->create(['name' => 'Car wash', 'category' => 'Cleaning', 'quantity' => 1]);

        $this->getJson("/api/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.maintenance_records.0.maintenance.other.name', 'Car wash')
            ->assertJsonPath('data.maintenance_records.0.maintenance.other.category', 'Cleaning')
            ->assertJsonPath('data.maintenance_records.0.maintenance.other.price', 15)
            ->assertJsonPath('data.maintenance_records.0.maintenance.other.technician_name', 'Sami');

        $this->getJson("/api/maintenance-records/{$record->id}")
            ->assertOk()
            ->assertJsonPath('data.other_service.name', 'Car wash');
    }

    public function test_app_api_creates_and_updates_other_services(): void
    {
        $vehicle = $this->vehicle();

        $recordId = $this->postJson('/api/maintenance-records', [
            'vehicle_id' => $vehicle->id,
            'maintenance_date' => '2026-11-08 09:00:00',
            'type' => 'other',
        ])->assertCreated()->json('data.id');

        $this->postJson('/api/other-services', ['record_id' => $recordId])->assertUnprocessable();

        $id = $this->postJson('/api/other-services', [
            'record_id' => $recordId,
            'name' => 'Wiper blades',
            'category' => 'Visibility',
            'quantity' => 2,
        ])->assertCreated()->json('data.id');

        $this->putJson("/api/other-services/{$id}", ['note' => 'Rear too'])
            ->assertOk()
            ->assertJsonPath('data.note', 'Rear too')
            ->assertJsonPath('data.name', 'Wiper blades');
    }

    // ---- current reading on every type ----

    public function test_admin_saves_a_reading_on_a_steering_oil_change_in_the_cars_unit(): void
    {
        $this->admin();
        $vehicle = $this->vehicle('miles');

        Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm([
                'user_id' => $this->admin->id,
                'vehicle_id' => $vehicle->id,
                'type' => 'steering_oil_change',
                'oil_type' => 'ATF',
                'quantity' => '1',
                'current_reading' => '40000',
                'maintenance_date' => now()->toDateTimeString(),
            ])
            ->assertFormFieldIsVisible('current_reading')
            ->call('create')
            ->assertHasNoFormErrors();

        $record = MaintenanceRecord::latest('id')->first();
        $this->assertSame(40000, $record->current_reading);
        $this->assertSame('miles', $record->distance_unit);

        // switching the car later doesn't change the saved record
        $vehicle->update(['distance_unit' => 'km']);
        $this->assertSame('miles', $record->fresh()->distance_unit);
    }

    public function test_reading_is_optional_and_hidden_for_oil_changes(): void
    {
        $this->admin();
        $vehicle = $this->vehicle();

        Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm(['type' => 'oil_change'])
            ->assertFormFieldIsHidden('current_reading')
            ->assertFormFieldIsVisible('current_km');

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

        $record = MaintenanceRecord::latest('id')->first();
        $this->assertNull($record->current_reading);
        $this->assertNull($record->distance_unit);
    }

    public function test_detail_edit_page_shows_and_saves_the_reading(): void
    {
        $this->admin();
        $record = $this->record($this->vehicle('miles'), 'steering_oil_change');
        $record->update(['current_reading' => 30000]);
        $steering = $record->steeringOilChange()->create(['oil_type' => 'ATF', 'quantity' => 1]);

        Livewire::test(\App\Filament\Resources\SteeringOilChangeResource\Pages\EditSteeringOilChange::class, ['record' => $steering->getRouteKey()])
            ->assertFormSet(['current_reading' => 30000])
            ->fillForm(['current_reading' => '31000'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(31000, $record->fresh()->current_reading);
        $this->assertSame('miles', $record->fresh()->distance_unit);

        $this->get(\App\Filament\Resources\SteeringOilChangeResource::getUrl('index'))
            ->assertOk()
            ->assertSee('31,000 mi');
    }

    public function test_oil_change_edit_page_leaves_the_record_reading_alone(): void
    {
        $this->admin();
        $record = $this->record($this->vehicle());
        $oil = OilChange::create(['record_id' => $record->id, 'current_km' => '52000', 'oil_type' => '5W', 'oil_quantity' => '4', 'filter' => 'OEM']);

        Livewire::test(\App\Filament\Resources\OilChangeResource\Pages\EditOilChange::class, ['record' => $oil->getRouteKey()])
            ->assertFormFieldDoesNotExist('current_reading')
            ->fillForm(['price' => '30'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($record->fresh()->current_reading);
        $this->assertSame(30.0, $record->fresh()->price);
    }

    public function test_records_list_shows_readings_for_every_type(): void
    {
        $this->admin();
        $vehicle = $this->vehicle('miles');
        $steering = $this->record($vehicle, 'steering_oil_change');
        $steering->update(['current_reading' => 30000]);
        OilChange::create(['record_id' => $this->record($vehicle)->id, 'current_km' => '40000']);

        Livewire::test(\App\Filament\Resources\MaintenanceRecordResource\Pages\ListMaintenanceRecords::class)
            ->assertSee('30,000 mi')
            ->assertSee('40,000 mi');
    }

    public function test_history_api_gives_every_service_a_reading_and_unit(): void
    {
        $vehicle = $this->vehicle('miles');
        $steering = $this->record($vehicle, 'steering_oil_change');
        $steering->update(['current_reading' => 30000, 'maintenance_date' => '2026-11-01 10:00:00']);
        $steering->steeringOilChange()->create(['oil_type' => 'ATF', 'quantity' => 1]);

        $oilRecord = $this->record($vehicle);
        OilChange::create(['record_id' => $oilRecord->id, 'current_km' => '40000']);

        $battery = $this->record($vehicle, 'battery_service');
        $battery->update(['maintenance_date' => '2026-11-09 10:00:00']);
        $battery->batteryService()->create(['brand' => 'Bosch', 'size' => '70Ah']);

        $this->getJson("/api/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.maintenance_records.0.maintenance.steering_oil_change.current_reading', 30000)
            ->assertJsonPath('data.maintenance_records.0.maintenance.steering_oil_change.distance_unit', 'miles')
            ->assertJsonPath('data.maintenance_records.1.maintenance.oil_change.current_reading', 40000)
            ->assertJsonPath('data.maintenance_records.1.maintenance.oil_change.distance_unit', 'miles')
            ->assertJsonPath('data.maintenance_records.2.maintenance.battery_service.current_reading', null)
            ->assertJsonPath('data.maintenance_records.2.maintenance.battery_service.distance_unit', 'miles');
    }

    public function test_app_can_send_a_reading_with_a_maintenance_record(): void
    {
        $vehicle = $this->vehicle('miles');

        $this->postJson('/api/maintenance-records', [
            'vehicle_id' => $vehicle->id,
            'maintenance_date' => '2026-11-08 09:00:00',
            'type' => 'wheel_service',
            'current_reading' => 25000,
        ])->assertCreated()
            ->assertJsonPath('data.current_reading', 25000)
            ->assertJsonPath('data.distance_unit', 'miles');

        $this->postJson('/api/maintenance-records', [
            'vehicle_id' => $vehicle->id,
            'maintenance_date' => '2026-11-08 09:00:00',
            'type' => 'wheel_service',
            'current_reading' => -5,
        ])->assertUnprocessable();
    }

    // ---- next change for every type ----

    public function test_intervals_per_type_in_km_and_miles(): void
    {
        $expected = [
            'oil_change' => [5000, 3107],
            'steering_oil_change' => [60000, 37282],
            'transmission_oil_change' => [60000, 37282],
            'differential_oil_change' => [60000, 37282],
            'gear_oil_change' => [60000, 37282],
            'wheel_service' => [10000, 6214],
            'battery_service' => [null, null],
            'other' => [null, null],
        ];

        foreach ($expected as $type => [$km, $mi]) {
            $this->assertSame($km, \App\Support\Distance::interval($type, 'km'), "{$type} km");
            $this->assertSame($mi, \App\Support\Distance::interval($type, 'miles'), "{$type} miles");
        }
    }

    public function test_next_change_is_filled_from_the_reading_per_type(): void
    {
        $miles = $this->vehicle('miles');
        $km = $this->vehicle('km');

        $steering = MaintenanceRecord::create(['vehicle_id' => $km->id, 'maintenance_date' => now(), 'type' => 'steering_oil_change', 'current_reading' => 40000]);
        $wheels = MaintenanceRecord::create(['vehicle_id' => $miles->id, 'maintenance_date' => now(), 'type' => 'wheel_service', 'current_reading' => 20000]);
        $battery = MaintenanceRecord::create(['vehicle_id' => $km->id, 'maintenance_date' => now(), 'type' => 'battery_service', 'current_reading' => 40000]);
        $typed = MaintenanceRecord::create(['vehicle_id' => $km->id, 'maintenance_date' => now(), 'type' => 'gear_oil_change', 'current_reading' => 40000, 'next_change_reading' => 90000]);

        $this->assertSame(100000, $steering->next_change_reading);
        $this->assertSame(26214, $wheels->next_change_reading);
        $this->assertSame('miles', $wheels->distance_unit);
        $this->assertNull($battery->next_change_reading);
        $this->assertSame(90000, $typed->next_change_reading);
    }

    public function test_admin_form_fills_next_change_for_steering_in_miles(): void
    {
        $this->admin();
        $vehicle = $this->vehicle('miles');

        Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm([
                'user_id' => $this->admin->id,
                'vehicle_id' => $vehicle->id,
                'type' => 'steering_oil_change',
                'oil_type' => 'ATF',
                'quantity' => '1',
                'maintenance_date' => now()->toDateTimeString(),
            ])
            ->fillForm(['current_reading' => '40000'])
            ->assertFormSet(['next_change_reading' => 77282])
            ->assertSee('Filled automatically: +37,282 mi')
            ->call('create')
            ->assertHasNoFormErrors();

        $record = MaintenanceRecord::latest('id')->first();
        $this->assertSame(40000, $record->current_reading);
        $this->assertSame(77282, $record->next_change_reading);
    }

    public function test_battery_and_other_next_change_is_entered_by_hand(): void
    {
        $this->admin();
        $vehicle = $this->vehicle();

        Livewire::test(CreateMaintenanceRecord::class)
            ->fillForm([
                'user_id' => $this->admin->id,
                'vehicle_id' => $vehicle->id,
                'type' => 'battery_service',
                'brand' => 'Bosch',
                'size' => '70Ah',
                'maintenance_date' => now()->toDateTimeString(),
            ])
            ->fillForm(['current_reading' => '40000'])
            ->assertFormSet(['next_change_reading' => null])
            ->assertSee('Enter it yourself')
            ->fillForm(['next_change_reading' => '100000'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(100000, MaintenanceRecord::latest('id')->first()->next_change_reading);
    }

    public function test_detail_edit_page_fills_and_saves_next_change(): void
    {
        $this->admin();
        $record = $this->record($this->vehicle(), 'transmission_oil_change');
        $transmission = $record->transmissionOilChange()->create(['oil_type' => 'ATF', 'quantity' => 6]);

        Livewire::test(\App\Filament\Resources\TransmissionOilChangeResource\Pages\EditTransmissionOilChange::class, ['record' => $transmission->getRouteKey()])
            ->fillForm(['current_reading' => '30000'])
            ->assertFormSet(['next_change_reading' => 90000])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $record->fresh();
        $this->assertSame(30000, $fresh->current_reading);
        $this->assertSame(90000, $fresh->next_change_reading);

        $this->get(\App\Filament\Resources\TransmissionOilChangeResource::getUrl('index'))
            ->assertSee('30,000 km')
            ->assertSee('90,000 km');
    }

    public function test_history_api_and_list_show_next_change_for_every_type(): void
    {
        $this->admin();
        $vehicle = $this->vehicle();
        $diff = MaintenanceRecord::create(['vehicle_id' => $vehicle->id, 'maintenance_date' => '2026-11-01 10:00:00', 'type' => 'differential_oil_change', 'current_reading' => 10000]);
        $diff->differentialOilChange()->create(['oil_type' => '75W-90', 'quantity' => 1]);
        $oilRecord = MaintenanceRecord::create(['vehicle_id' => $vehicle->id, 'maintenance_date' => '2026-11-05 10:00:00', 'type' => 'oil_change']);
        OilChange::create(['record_id' => $oilRecord->id, 'current_km' => '52000']);

        $this->getJson("/api/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.maintenance_records.0.maintenance.differential_oil_change.next_change_reading', 70000)
            ->assertJsonPath('data.maintenance_records.1.maintenance.oil_change.next_change_reading', 57000);

        Livewire::test(\App\Filament\Resources\MaintenanceRecordResource\Pages\ListMaintenanceRecords::class)
            ->assertSee('70,000 km')
            ->assertSee('57,000 km');
    }

    public function test_app_gets_next_change_when_sending_a_reading(): void
    {
        $vehicle = $this->vehicle('miles');

        $this->postJson('/api/maintenance-records', [
            'vehicle_id' => $vehicle->id,
            'maintenance_date' => '2026-11-08 09:00:00',
            'type' => 'gear_oil_change',
            'current_reading' => 10000,
        ])->assertCreated()
            ->assertJsonPath('data.next_change_reading', 47282);
    }

    public function test_existing_types_still_work_unchanged(): void
    {
        $vehicle = $this->vehicle();
        $record = $this->record($vehicle, 'battery_service');
        $record->syncTypeDetails(['brand' => 'Bosch', 'size' => '70Ah']);

        $this->getJson("/api/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.maintenance_records.0.maintenance.battery_service.brand', 'Bosch');
    }
}

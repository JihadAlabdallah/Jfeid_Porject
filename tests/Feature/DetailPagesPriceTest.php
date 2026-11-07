<?php

namespace Tests\Feature;

use App\Filament\Resources;
use App\Filament\Resources\MaintenanceRecordResource;
use App\Filament\Resources\MaintenanceRecordResource\Pages\CreateMaintenanceRecord;
use App\Filament\Resources\MaintenanceRecordResource\Pages\EditMaintenanceRecord;
use App\Filament\Resources\OilChangeResource\Pages\EditOilChange;
use App\Models\MaintenanceRecord;
use App\Models\OilChange;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The detail pages (Oil Changes, Battery Services, ...): "New" goes through
 * the main Maintenance Records form; edit pages keep record_id read-only and
 * edit technician/price on the parent record.
 */
class DetailPagesPriceTest extends TestCase
{
    use RefreshDatabase;

    /** resource => maintenance type */
    private const RESOURCES = [
        Resources\OilChangeResource::class => 'oil_change',
        Resources\SteeringOilChangeResource::class => 'steering_oil_change',
        Resources\BatteryServiceResource::class => 'battery_service',
        Resources\WheelServiceResource::class => 'wheel_service',
        Resources\TransmissionOilChangeResource::class => 'transmission_oil_change',
        Resources\DifferentialOilChangeResource::class => 'differential_oil_change',
        Resources\GearOilChangeResource::class => 'gear_oil_change',
        Resources\OtherServiceResource::class => 'other',
    ];

    private User $admin;

    private Vehicle $vehicle;

    private MaintenanceRecord $record;

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

        $this->vehicle = Vehicle::create(['user_id' => $this->admin->id, 'type' => 'Hilux']);
        $this->record = MaintenanceRecord::create([
            'vehicle_id' => $this->vehicle->id,
            'maintenance_date' => '2026-11-01 10:00:00',
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

    // ---- "New" goes to the main form ----

    public function test_new_button_on_every_detail_page_opens_main_form_with_type(): void
    {
        foreach (self::RESOURCES as $resource => $type) {
            $expected = MaintenanceRecordResource::getUrl('create', ['type' => $type]);

            $this->get($resource::getUrl('index'))
                ->assertOk()
                ->assertSee(e($expected), false);
        }
    }

    public function test_old_detail_create_address_redirects_to_main_form(): void
    {
        foreach (self::RESOURCES as $resource => $type) {
            $this->get($resource::getUrl('create'))
                ->assertRedirect(MaintenanceRecordResource::getUrl('create', ['type' => $type]));
        }
    }

    public function test_old_record_id_link_opens_that_record(): void
    {
        $this->get(Resources\BatteryServiceResource::getUrl('create') . '?record_id=' . $this->record->id)
            ->assertRedirect(MaintenanceRecordResource::getUrl('edit', ['record' => $this->record->id]));
    }

    public function test_main_form_preselects_type_and_ignores_invalid_type(): void
    {
        Livewire::withQueryParams(['type' => 'steering_oil_change'])
            ->test(CreateMaintenanceRecord::class)
            ->assertFormSet(['type' => 'steering_oil_change']);

        Livewire::withQueryParams(['type' => 'not_a_type'])
            ->test(CreateMaintenanceRecord::class)
            ->assertFormSet(['type' => null]);
    }

    public function test_every_type_created_from_main_form_gets_details_with_correct_record_id(): void
    {
        foreach (MaintenanceRecord::TYPE_DETAILS as $type => $detail) {
            $fields = array_fill_keys($detail['fields'], '4');

            Livewire::withQueryParams(['type' => $type])
                ->test(CreateMaintenanceRecord::class)
                ->fillForm($fields + [
                    'user_id' => $this->admin->id,
                    'vehicle_id' => $this->vehicle->id,
                    'maintenance_date' => now()->toDateTimeString(),
                    'price' => '10',
                ])
                ->call('create')
                ->assertHasNoFormErrors();

            $record = MaintenanceRecord::where('type', $type)->latest('id')->first();
            $related = $record->{$detail['relation']};

            $this->assertNotNull($related, "{$type}: details missing");
            $this->assertSame($record->id, (int) $related->record_id, "{$type}: wrong record_id");
            $this->assertSame(10.0, $record->price);
        }
    }

    // ---- edit pages ----

    public function test_edit_page_shows_record_read_only_with_readable_label(): void
    {
        $oil = OilChange::create($this->oilFields());

        $this->get(Resources\OilChangeResource::getUrl('edit', ['record' => $oil]))
            ->assertOk()
            ->assertSee('#' . $this->record->id . ' · Admin · Hilux · 01 Nov 2026');
    }

    public function test_editing_details_never_changes_record_id(): void
    {
        $other = MaintenanceRecord::create([
            'vehicle_id' => $this->vehicle->id,
            'maintenance_date' => now(),
            'type' => 'battery_service',
        ]);
        $oil = OilChange::create($this->oilFields());

        Livewire::test(EditOilChange::class, ['record' => $oil->getRouteKey()])
            ->fillForm(['record_id' => $other->id, 'oil_type' => '10W-40'])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $oil->fresh();
        $this->assertSame($this->record->id, (int) $fresh->record_id);
        $this->assertSame('10W-40', $fresh->oil_type);
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

    public function test_negative_price_is_rejected_on_edit(): void
    {
        $oil = OilChange::create($this->oilFields());

        Livewire::test(EditOilChange::class, ['record' => $oil->getRouteKey()])
            ->fillForm(['price' => '-1'])
            ->call('save')
            ->assertHasFormErrors(['price']);
    }

    // ---- records missing their details ----

    public function test_record_without_details_can_be_completed_from_maintenance_records_edit(): void
    {
        $this->assertNull($this->record->oilChange);

        Livewire::test(EditMaintenanceRecord::class, ['record' => $this->record->getRouteKey()])
            ->fillForm([
                'oil_type' => '5W-30',
                'oil_quantity' => 4,
                'current_km' => 50000,
                'next_change_km' => 55000,
                'filter' => 'OEM',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $details = $this->record->fresh()->oilChange;
        $this->assertNotNull($details);
        $this->assertSame($this->record->id, (int) $details->record_id);
    }

    // ---- lists ----

    public function test_every_detail_list_page_loads_with_price_columns(): void
    {
        OilChange::create($this->oilFields());
        $this->record->update(['price' => 12.5, 'technician_name' => 'Ahmed']);

        foreach (array_keys(self::RESOURCES) as $resource) {
            $this->get($resource::getUrl('index'))->assertOk()->assertSee('Price');
        }

        $this->get(Resources\OilChangeResource::getUrl('index'))
            ->assertSee('Ahmed')
            ->assertSee('$12.50');
    }

    // ---- the phone app's API flow is unchanged ----

    public function test_app_api_flow_record_then_details_still_works(): void
    {
        $recordId = $this->postJson('/api/maintenance-records', [
            'vehicle_id' => $this->vehicle->id,
            'maintenance_date' => '2026-11-07 09:00:00',
            'type' => 'steering_oil_change',
        ])->assertCreated()->json('data.id');

        $this->postJson('/api/steering-oil-changes', [
            'record_id' => $recordId,
            'oil_type' => 'ATF',
            'quantity' => 1,
        ])->assertSuccessful();

        $this->getJson("/api/vehicles/{$this->vehicle->id}")
            ->assertOk()
            ->assertJsonFragment(['oil_type' => 'ATF', 'record_id' => $recordId]);
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\VehicleResource\Pages\CreateVehicle;
use App\Filament\Resources\VehicleResource\Pages\ListVehicles;
use App\Models\MaintenanceRecord;
use App\Models\OilChange;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Car plate per vehicle, current reading per vehicle, and the vehicle list
 * in /my-vehicles, /profile and sign-in.
 */
class VehicleCarplateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'maryam test',
            'email' => 'maryam@example.com',
            'phonenumber' => '71653908',
            'password' => 'secret123',
            'carplate' => 'G112233',
        ]);
    }

    private function car(string $type, ?string $plate = null, string $unit = 'km', ?User $owner = null): Vehicle
    {
        return Vehicle::create([
            'user_id' => ($owner ?? $this->user)->id,
            'type' => $type,
            'carplate' => $plate,
            'distance_unit' => $unit,
        ]);
    }

    private function service(Vehicle $car, string $type, string $date, ?int $reading): MaintenanceRecord
    {
        return MaintenanceRecord::create([
            'vehicle_id' => $car->id,
            'type' => $type,
            'maintenance_date' => $date,
            'current_reading' => $reading,
        ]);
    }

    private function oilChange(Vehicle $car, string $date, string $km): MaintenanceRecord
    {
        $record = MaintenanceRecord::create([
            'vehicle_id' => $car->id,
            'type' => 'oil_change',
            'maintenance_date' => $date,
        ]);

        OilChange::create([
            'record_id' => $record->id,
            'current_km' => $km,
            'oil_type' => '5W-30',
        ]);

        return $record;
    }

    public function test_migration_copies_the_users_plate_to_their_oldest_car_only(): void
    {
        $migration = require database_path('migrations/2026_11_11_000001_add_carplate_to_vehicles.php');
        $migration->down();

        $other = User::create(['name' => 'no plate', 'email' => 'np@example.com', 'phonenumber' => '1', 'password' => 'x12']);
        $first = DB::table('vehicles')->insertGetId(['user_id' => $this->user->id, 'type' => 'TOYOTA', 'distance_unit' => 'km']);
        $second = DB::table('vehicles')->insertGetId(['user_id' => $this->user->id, 'type' => 'Nissan', 'distance_unit' => 'km']);
        $noPlate = DB::table('vehicles')->insertGetId(['user_id' => $other->id, 'type' => 'Kia', 'distance_unit' => 'km']);

        $migration->up();

        $this->assertSame('G112233', DB::table('vehicles')->where('id', $first)->value('carplate'));
        $this->assertNull(DB::table('vehicles')->where('id', $second)->value('carplate'));
        $this->assertNull(DB::table('vehicles')->where('id', $noPlate)->value('carplate'));
        // the user's own plate is untouched
        $this->assertSame('G112233', $this->user->fresh()->carplate);
        $this->assertSame(3, DB::table('vehicles')->count());
    }

    public function test_current_reading_is_from_the_latest_service_with_a_reading(): void
    {
        $car = $this->car('TOYOTA');
        $this->assertNull($car->currentReading());

        $this->oilChange($car, '2026-11-01 10:00:00', '5,000');
        $this->service($car, 'differential_oil_change', '2026-11-06 10:00:00', 7000);
        $this->service($car, 'battery_service', '2026-11-07 10:00:00', null); // no reading: skipped

        $this->assertSame(7000, $car->currentReading());

        // an older service entered later does not win over the newest date
        $this->service($car, 'wheel_service', '2026-10-01 10:00:00', 1000);
        $this->assertSame(7000, $car->currentReading());

        // an oil change reading counts too
        $this->oilChange($car, '2026-11-09 10:00:00', '7500');
        $this->assertSame(7500, $car->currentReading());
    }

    public function test_current_reading_is_shown_in_the_cars_current_unit(): void
    {
        $car = $this->car('Nissan');
        $this->service($car, 'wheel_service', '2026-11-01 10:00:00', 16093); // km

        $car->update(['distance_unit' => 'miles']);

        $this->assertSame(10000, $car->fresh()->currentReading());
    }

    public function test_my_vehicles_returns_plate_and_reading_for_each_car(): void
    {
        $toyota = $this->car('TOYOTA', 'G112233');
        $nissan = $this->car('Nissan', 'G112255');
        $this->service($toyota, 'wheel_service', '2026-11-01 10:00:00', 5000);
        $this->service($nissan, 'wheel_service', '2026-11-01 10:00:00', 7000);
        $this->car('Other person car', 'X1', 'km', User::create(['name' => 'o', 'email' => 'o@example.com', 'phonenumber' => '2', 'password' => 'x12']));

        Sanctum::actingAs($this->user);

        $this->getJson('/api/my-vehicles')
            ->assertOk()
            ->assertExactJson([
                'status' => 'success',
                'data' => [
                    ['id' => $toyota->id, 'type' => 'TOYOTA', 'carplate' => 'G112233', 'current_reading' => 5000, 'distance_unit' => 'km'],
                    ['id' => $nissan->id, 'type' => 'Nissan', 'carplate' => 'G112255', 'current_reading' => 7000, 'distance_unit' => 'km'],
                ],
            ]);
    }

    public function test_a_car_added_by_the_admin_shows_up_without_signing_in_again(): void
    {
        $this->car('TOYOTA', 'G112233');
        Sanctum::actingAs($this->user);

        $this->getJson('/api/my-vehicles')->assertOk()->assertJsonCount(1, 'data');

        $this->car('Nissan', 'G112255'); // added from the dashboard

        $this->getJson('/api/my-vehicles')->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.type', 'Nissan');
        $this->getJson('/api/profile')->assertOk()->assertJsonCount(2, 'data.vehicles');
    }

    public function test_profile_keeps_the_user_fields_and_adds_the_vehicles(): void
    {
        $car = $this->car('TOYOTA', 'G112233');
        Sanctum::actingAs($this->user);

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $this->user->id)
            ->assertJsonPath('data.name', 'maryam test')
            ->assertJsonPath('data.phonenumber', '71653908')
            ->assertJsonPath('data.carplate', 'G112233')
            ->assertJsonPath('data.vehicles.0.id', $car->id)
            ->assertJsonPath('data.vehicles.0.carplate', 'G112233')
            ->assertJsonPath('data.vehicles.0.current_reading', null)
            ->assertJsonMissingPath('data.password');
    }

    public function test_sign_in_still_returns_vehicle_id_and_now_the_full_vehicle_list(): void
    {
        $toyota = $this->car('TOYOTA', 'G112233');
        $this->car('Nissan', 'G112255');

        $this->postJson('/api/signin', ['name_or_email' => 'maryam@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('vehicle_id', $toyota->id)
            ->assertJsonCount(2, 'vehicles')
            ->assertJsonPath('vehicles.1.carplate', 'G112255')
            ->assertJsonStructure(['token', 'vehicles' => [['id', 'type', 'carplate', 'current_reading', 'distance_unit']]]);
    }

    public function test_add_and_edit_a_car_with_its_plate(): void
    {
        Sanctum::actingAs($this->user);

        $id = $this->postJson('/api/my-vehicles', ['type' => 'Nissan', 'carplate' => 'G112255'])
            ->assertCreated()
            ->assertJsonPath('data.carplate', 'G112255')
            ->assertJsonPath('data.current_reading', null)
            ->json('data.id');

        $this->patchJson("/api/my-vehicles/{$id}", ['carplate' => 'G999'])
            ->assertOk()
            ->assertJsonPath('data.carplate', 'G999')
            ->assertJsonPath('data.type', 'Nissan');

        $this->patchJson("/api/my-vehicles/{$id}", ['carplate' => str_repeat('A', 21)])->assertUnprocessable();
    }

    public function test_cannot_edit_another_users_car(): void
    {
        $other = User::create(['name' => 'o', 'email' => 'o@example.com', 'phonenumber' => '2', 'password' => 'x12']);
        $theirs = $this->car('Kia', 'K1', 'km', $other);
        Sanctum::actingAs($this->user);

        $this->patchJson("/api/my-vehicles/{$theirs->id}", ['carplate' => 'HACK'])->assertNotFound();
        $this->assertSame('K1', $theirs->fresh()->carplate);
    }

    public function test_profile_plate_update_follows_the_only_car_but_not_when_there_are_several(): void
    {
        $car = $this->car('TOYOTA', 'G112233');
        Sanctum::actingAs($this->user);

        $this->postJson('/api/profile/update/x', ['carplate' => 'NEW1'])->assertOk();
        $this->assertSame('NEW1', $car->fresh()->carplate);

        $second = $this->car('Nissan', 'G112255');
        $this->postJson('/api/profile/update/x', ['carplate' => 'NEW2'])->assertOk();
        $this->assertSame('NEW1', $car->fresh()->carplate);
        $this->assertSame('G112255', $second->fresh()->carplate);
        $this->assertSame('NEW2', $this->user->fresh()->carplate);

        // updating something else leaves the plates alone
        $this->postJson('/api/profile/update/x', ['name' => 'maryam'])->assertOk();
        $this->assertSame('NEW1', $car->fresh()->carplate);
    }

    public function test_signup_puts_the_plate_on_the_new_car(): void
    {
        $this->withHeader('User-Agent', 'Flutter')
            ->postJson('/api/signup', [
                'name' => 'new client',
                'email' => 'new@example.com',
                'phonenumber' => '123',
                'password' => 'secret',
                'carplate' => 'B777',
                'vehicle_type' => 'Kia',
            ])->assertCreated();

        $user = User::where('email', 'new@example.com')->first();
        $this->assertSame('B777', $user->carplate);
        $this->assertSame('B777', $user->vehicles()->first()->carplate);
    }

    public function test_car_history_has_plate_and_reading_and_still_has_user(): void
    {
        $car = $this->car('TOYOTA', 'G112233');
        $this->service($car, 'wheel_service', '2026-11-01 10:00:00', 5000);

        $this->getJson("/api/vehicles/{$car->id}")
            ->assertOk()
            ->assertJsonPath('data.carplate', 'G112233')
            ->assertJsonPath('data.current_reading', 5000)
            ->assertJsonPath('data.user.id', $this->user->id) // kept until the new app is out
            ->assertJsonCount(1, 'data.maintenance_records');
    }

    public function test_dashboard_vehicle_plate_can_be_set_and_searched(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'phonenumber' => '0', 'password' => 'x12', 'is_admin' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('jfeid'));

        Livewire::test(CreateVehicle::class)
            ->fillForm(['user_id' => $this->user->id, 'type' => 'Nissan', 'carplate' => 'G112255', 'distance_unit' => 'km'])
            ->call('create')
            ->assertHasNoFormErrors();

        $nissan = Vehicle::where('type', 'Nissan')->first();
        $this->assertSame('G112255', $nissan->carplate);

        $toyota = $this->car('TOYOTA', 'G112233');

        Livewire::test(ListVehicles::class)
            ->assertCanSeeTableRecords([$nissan, $toyota])
            ->searchTable('G112255')
            ->assertCanSeeTableRecords([$nissan])
            ->assertCanNotSeeTableRecords([$toyota]);
    }
}

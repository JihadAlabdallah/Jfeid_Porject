<?php

namespace Tests\Feature;

use App\Models\MaintenanceRecord;
use App\Models\Notification;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\FcmService;
use App\Support\Revenue;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WeekendFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attrs = []): User
    {
        return User::create($attrs + [
            'name' => 'Client ' . uniqid(),
            'email' => uniqid() . '@example.com',
            'phonenumber' => '0500000000',
            'password' => 'secret123',
        ]);
    }

    private function record(Vehicle $vehicle, string $date, ?float $price): MaintenanceRecord
    {
        return MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'maintenance_date' => $date,
            'type' => 'oil_change',
            'price' => $price,
        ]);
    }

    // ---- change phone number / profile ----

    public function test_profile_update_route_works_and_changes_phone_number(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);

        $this->postJson('/api/profile/update/anything', ['phonenumber' => '0599999999'])
            ->assertOk()
            ->assertJsonPath('data.phonenumber', '0599999999');

        $this->assertSame('0599999999', $user->fresh()->phonenumber);
    }

    public function test_profile_update_accepts_capitalised_phone_field(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);

        $this->postJson('/api/profile/update/x', ['Phonenumber' => '0511111111'])->assertOk();

        $this->assertSame('0511111111', $user->fresh()->phonenumber);
    }

    public function test_profile_update_only_changes_sent_fields(): void
    {
        $user = $this->user(['name' => 'Keep Me', 'carplate' => 'ABC123']);
        Sanctum::actingAs($user);

        $this->postJson('/api/profile/update/x', ['phonenumber' => '0522222222'])->assertOk();

        $fresh = $user->fresh();
        $this->assertSame('Keep Me', $fresh->name);
        $this->assertSame('ABC123', $fresh->carplate);
    }

    public function test_profile_update_requires_login(): void
    {
        $this->postJson('/api/profile/update/x', ['phonenumber' => '1'])->assertUnauthorized();
    }

    // ---- km / miles ----

    public function test_users_default_to_km_and_can_switch_to_miles(): void
    {
        $user = $this->user();
        $this->assertSame('km', $user->fresh()->distance_unit);

        Sanctum::actingAs($user);
        $this->postJson('/api/profile/update/x', ['distance_unit' => 'miles'])
            ->assertOk()
            ->assertJsonPath('data.distance_unit', 'miles');

        $this->getJson('/api/profile')->assertJsonPath('data.distance_unit', 'miles');
    }

    public function test_invalid_distance_unit_is_rejected(): void
    {
        Sanctum::actingAs($this->user());

        $this->postJson('/api/profile/update/x', ['distance_unit' => 'm'])->assertUnprocessable();
    }

    // ---- multiple vehicles ----

    public function test_my_vehicles_returns_only_the_signed_in_users_vehicles(): void
    {
        $me = $this->user();
        $other = $this->user();
        Vehicle::create(['user_id' => $me->id, 'type' => 'Hilux']);
        Vehicle::create(['user_id' => $me->id, 'type' => 'Corolla']);
        Vehicle::create(['user_id' => $other->id, 'type' => 'Not mine']);

        Sanctum::actingAs($me);

        $this->getJson('/api/my-vehicles')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['type' => 'Not mine']);
    }

    public function test_user_can_add_a_second_vehicle(): void
    {
        $me = $this->user();
        Vehicle::create(['user_id' => $me->id, 'type' => 'Hilux']);
        Sanctum::actingAs($me);

        $this->postJson('/api/my-vehicles', ['type' => 'Land Cruiser'])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $me->id);

        $this->assertSame(2, $me->vehicles()->count());
    }

    public function test_my_vehicles_requires_login(): void
    {
        $this->getJson('/api/my-vehicles')->assertUnauthorized();
    }

    public function test_signin_keeps_vehicle_id_and_adds_vehicle_list(): void
    {
        $me = $this->user(['email' => 'me@example.com']);
        $first = Vehicle::create(['user_id' => $me->id, 'type' => 'Hilux']);
        Vehicle::create(['user_id' => $me->id, 'type' => 'Corolla']);

        $this->postJson('/api/signin', ['name_or_email' => 'me@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('vehicle_id', $first->id)
            ->assertJsonCount(2, 'vehicles');
    }

    // ---- technician + price ----

    public function test_api_returns_technician_and_price(): void
    {
        $vehicle = Vehicle::create(['user_id' => $this->user()->id, 'type' => 'Hilux']);
        $record = $this->record($vehicle, '2026-11-01 10:00:00', 45.5);
        $record->update(['technician_name' => 'Ahmed']);

        $this->getJson("/api/maintenance-records/{$record->id}")
            ->assertOk()
            ->assertJsonPath('data.technician_name', 'Ahmed')
            ->assertJsonPath('data.price', 45.5);
    }

    public function test_vehicle_history_includes_technician_and_price(): void
    {
        $vehicle = Vehicle::create(['user_id' => $this->user()->id, 'type' => 'Hilux']);
        $record = MaintenanceRecord::create([
            'vehicle_id' => $vehicle->id,
            'maintenance_date' => '2026-11-01 10:00:00',
            'type' => 'battery_service',
            'technician_name' => 'Ahmed',
            'price' => 80,
        ]);
        $record->batteryService()->create(['brand' => 'Bosch', 'size' => '70Ah']);

        $this->getJson("/api/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.maintenance_records.0.maintenance.battery_service.brand', 'Bosch')
            ->assertJsonPath('data.maintenance_records.0.maintenance.battery_service.technician_name', 'Ahmed')
            ->assertJsonPath('data.maintenance_records.0.maintenance.battery_service.price', 80);
    }

    public function test_old_records_without_price_still_work(): void
    {
        $vehicle = Vehicle::create(['user_id' => $this->user()->id, 'type' => 'Hilux']);
        $record = $this->record($vehicle, '2026-11-01 10:00:00', null);

        $this->getJson("/api/maintenance-records/{$record->id}")
            ->assertOk()
            ->assertJsonPath('data.price', null)
            ->assertJsonPath('data.technician_name', null);
    }

    // ---- revenue ----

    public function test_revenue_periods(): void
    {
        $vehicle = Vehicle::create(['user_id' => $this->user()->id, 'type' => 'Hilux']);
        $now = Carbon::parse('2026-11-07 12:00:00');

        $this->record($vehicle, '2026-11-02 09:00:00', 100);   // this month
        $this->record($vehicle, '2026-11-30 23:00:00', 50);    // this month, later day
        $this->record($vehicle, '2026-06-15 09:00:00', 20);    // 6-month window starts in June
        $this->record($vehicle, '2026-05-31 09:00:00', 1000);  // outside 6 months, inside the year
        $this->record($vehicle, '2025-12-31 09:00:00', 7);     // last year
        $this->record($vehicle, '2026-11-03 09:00:00', null);  // no price

        $this->assertSame(150.0, Revenue::thisMonth($now));
        $this->assertSame(170.0, Revenue::lastSixMonths($now));
        $this->assertSame(1170.0, Revenue::thisYear($now));

        $monthly = Revenue::monthly(6, $now);
        $this->assertSame(['Jun 2026', 'Jul 2026', 'Aug 2026', 'Sep 2026', 'Oct 2026', 'Nov 2026'], array_keys($monthly));
        $this->assertSame(150.0, $monthly['Nov 2026']);
        $this->assertSame(20.0, $monthly['Jun 2026']);
    }

    // ---- weekly notification ----

    private function fakeFcm(): FcmService
    {
        $fake = new class extends FcmService {
            public array $sent = [];

            public function isConfigured(): bool
            {
                return true;
            }

            public function sendToTopic(string $topic, string $title, string $body): array
            {
                $this->sent[] = compact('topic', 'title', 'body');

                return ['ok' => true, 'response' => ['name' => 'projects/x/messages/1']];
            }
        };
        $this->app->instance(FcmService::class, $fake);

        return $fake;
    }

    public function test_weekly_reminder_is_sent_to_all_users_topic(): void
    {
        $fcm = $this->fakeFcm();

        $this->artisan('notifications:weekly-reminder')->assertSuccessful();

        $this->assertCount(1, $fcm->sent);
        $this->assertSame('all_users', $fcm->sent[0]['topic']);
        $this->assertSame('Time to check your car', $fcm->sent[0]['title']);
        $this->assertSame(1, Notification::count());
    }

    public function test_weekly_reminder_is_not_sent_twice_in_a_week(): void
    {
        $fcm = $this->fakeFcm();

        $this->artisan('notifications:weekly-reminder')->assertSuccessful();
        $this->artisan('notifications:weekly-reminder')->assertSuccessful();

        $this->assertCount(1, $fcm->sent);
    }

    public function test_weekly_reminder_is_scheduled(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($e) => str_contains((string) $e->command, 'notifications:weekly-reminder'));

        $this->assertCount(1, $events);
        $this->assertSame('0 10 * * 6', $events->first()->expression);
    }
}

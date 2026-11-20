<?php

namespace Tests\Feature;

use App\Filament\Resources\NotificationResource;
use App\Filament\Resources\NotificationResource\Pages\ListNotifications;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\User;
use App\Services\FcmService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Device tokens, sending to all / selected customers from the dashboard,
 * and the protected /send-notification endpoint. Firebase is faked.
 */
class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'phonenumber' => '0', 'password' => 'secret123', 'is_admin' => true]);
    }

    /** Real FcmService, without Google auth; Firebase answers are faked with Http::fake. */
    private function firebase(array $unregisteredTokens = []): void
    {
        config(['services.firebase.credentials' => base64_encode('{"project_id":"jfeid-test"}')]);

        $this->app->bind(FcmService::class, fn () => new class extends FcmService {
            protected function accessToken(): string
            {
                return 'test-access-token';
            }
        });

        Http::fake(function (Request $request) use ($unregisteredTokens) {
            $token = $request['message']['token'] ?? null;

            if ($token !== null && in_array($token, $unregisteredTokens, true)) {
                return Http::response(['error' => [
                    'code' => 404,
                    'status' => 'NOT_FOUND',
                    'details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'UNREGISTERED']],
                ]], 404);
            }

            return Http::response(['name' => 'projects/jfeid-test/messages/1']);
        });
    }

    private function customer(string $name, array $tokens = []): User
    {
        $user = User::create(['name' => $name, 'email' => strtolower($name) . '@example.com', 'phonenumber' => '7' . strlen($name), 'password' => 'secret123']);

        foreach ($tokens as $token) {
            DeviceToken::create(['user_id' => $user->id, 'token' => $token, 'platform' => 'android']);
        }

        return $user;
    }

    /** @return array<int, string> tokens / topics Firebase was asked to send to */
    private function sentTargets(): array
    {
        return Http::recorded()
            ->map(fn ($pair) => $pair[0]['message']['token'] ?? 'topic:' . $pair[0]['message']['topic'])
            ->values()
            ->all();
    }

    private function panel(): void
    {
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('jfeid'));
    }

    // ---- API: device tokens ----

    public function test_app_registers_its_phone_after_login(): void
    {
        $maryam = $this->customer('Maryam');
        Sanctum::actingAs($maryam);

        $this->postJson('/api/device-tokens', ['token' => 'phone-1', 'platform' => 'ios'])->assertOk();
        $this->postJson('/api/device-tokens', ['token' => 'phone-1', 'platform' => 'ios'])->assertOk(); // again: no duplicate

        $this->assertSame(1, DeviceToken::count());
        $this->assertSame($maryam->id, DeviceToken::first()->user_id);
        $this->assertSame('ios', DeviceToken::first()->platform);
    }

    public function test_a_phone_belongs_to_whoever_signed_in_on_it_last(): void
    {
        $maryam = $this->customer('Maryam', ['shared-phone']);
        $ali = $this->customer('Ali');

        Sanctum::actingAs($ali);
        $this->postJson('/api/device-tokens', ['token' => 'shared-phone'])->assertOk();

        $this->assertSame($ali->id, DeviceToken::where('token', 'shared-phone')->value('user_id'));
        $this->assertSame(0, $maryam->deviceTokens()->count());
    }

    public function test_device_token_validation_and_login_required(): void
    {
        $this->postJson('/api/device-tokens', ['token' => 'x'])->assertUnauthorized();

        Sanctum::actingAs($this->customer('Maryam'));
        $this->postJson('/api/device-tokens', [])->assertUnprocessable();
        $this->postJson('/api/device-tokens', ['token' => 'x', 'platform' => 'windows'])->assertUnprocessable();
        $this->postJson('/api/device-tokens', ['token' => str_repeat('a', 513)])->assertUnprocessable();
    }

    public function test_app_removes_its_phone_on_logout_but_cannot_remove_others(): void
    {
        $maryam = $this->customer('Maryam', ['maryam-phone']);
        $this->customer('Ali', ['ali-phone']);
        Sanctum::actingAs($maryam);

        $this->deleteJson('/api/device-tokens', ['token' => 'ali-phone'])->assertOk();
        $this->assertSame(2, DeviceToken::count());

        $this->deleteJson('/api/device-tokens', ['token' => 'maryam-phone'])->assertOk();
        $this->assertSame(['ali-phone'], DeviceToken::pluck('token')->all());
    }

    public function test_signout_with_device_token_removes_that_phone(): void
    {
        $maryam = $this->customer('Maryam', ['maryam-phone', 'maryam-tablet']);
        $token = $maryam->createToken('authToken')->plainTextToken;

        $this->withToken($token)->postJson('/api/signout', ['device_token' => 'maryam-phone'])->assertOk();

        $this->assertSame(['maryam-tablet'], DeviceToken::pluck('token')->all());
    }

    public function test_signout_without_device_token_still_works(): void
    {
        $maryam = $this->customer('Maryam', ['maryam-phone']);
        $token = $maryam->createToken('authToken')->plainTextToken;

        $this->withToken($token)->postJson('/api/signout')->assertOk();
        $this->assertSame(1, DeviceToken::count());
    }

    // ---- API: /send-notification is admins only now ----

    public function test_send_notification_endpoint_is_closed_to_the_public_and_customers(): void
    {
        $this->firebase();
        $body = ['topic' => 'all_users', 'title' => 'Hacked', 'body' => 'spam'];

        $this->postJson('/api/send-notification', $body)->assertUnauthorized();

        Sanctum::actingAs($this->customer('Maryam'));
        $this->postJson('/api/send-notification', $body)->assertForbidden();

        Http::assertNothingSent();
        $this->assertSame(0, Notification::count());
    }

    // ---- dashboard: Notifications page ----

    public function test_send_to_all_customers_uses_the_all_users_topic(): void
    {
        $this->firebase();
        $this->panel();

        Livewire::test(ListNotifications::class)
            ->callAction('send', data: ['audience' => 'all', 'title' => 'Hello', 'body' => 'Check your oil'])
            ->assertHasNoActionErrors()
            ->assertNotified('Sent to all customers');

        $this->assertSame(['topic:all_users'], $this->sentTargets());
        Http::assertSent(fn (Request $r) => $r['message']['notification'] === ['title' => 'Hello', 'body' => 'Check your oil']);

        $log = Notification::sole();
        $this->assertSame('all', $log->audience);
        $this->assertSame('Sent', NotificationResource::result($log));
    }

    public function test_send_to_selected_customers_reaches_only_their_phones(): void
    {
        $this->firebase();
        $this->panel();
        $maryam = $this->customer('Maryam', ['maryam-phone', 'maryam-tablet']);
        $ali = $this->customer('Ali', ['ali-phone']);
        $noApp = $this->customer('Sami');
        $this->customer('Other', ['other-phone']); // not selected

        Livewire::test(ListNotifications::class)
            ->callAction('send', data: [
                'audience' => 'selected',
                'users' => [$maryam->id, $ali->id, $noApp->id],
                'title' => 'Your car is ready',
                'body' => 'Come pick it up',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Notification sent');

        $targets = $this->sentTargets();
        sort($targets);
        $this->assertSame(['ali-phone', 'maryam-phone', 'maryam-tablet'], $targets);

        $log = Notification::sole();
        $this->assertSame('selected', $log->audience);
        $this->assertSame([$maryam->id, $ali->id, $noApp->id], $log->recipients);
        $this->assertSame(3, $log->sent_count);
        $this->assertSame(0, $log->failed_count);
        $this->assertSame('3 delivered, 1 no app', NotificationResource::result($log));
        $this->assertSame('3 selected', NotificationResource::audienceLabel($log));
    }

    public function test_selected_customers_are_required_when_sending_to_selected(): void
    {
        $this->firebase();
        $this->panel();

        Livewire::test(ListNotifications::class)
            ->callAction('send', data: ['audience' => 'selected', 'users' => [], 'title' => 'Hi', 'body' => 'x'])
            ->assertHasActionErrors(['users' => 'required']);

        Livewire::test(ListNotifications::class)
            ->callAction('send', data: ['audience' => 'all', 'title' => '', 'body' => str_repeat('a', 256)])
            ->assertHasActionErrors(['title' => 'required', 'body' => 'max']);

        Http::assertNothingSent();
    }

    public function test_phone_that_removed_the_app_is_forgotten(): void
    {
        $this->firebase(unregisteredTokens: ['old-phone']);
        $this->panel();
        $maryam = $this->customer('Maryam', ['old-phone', 'new-phone']);

        Livewire::test(ListNotifications::class)
            ->callAction('send', data: ['audience' => 'selected', 'users' => [$maryam->id], 'title' => 'Hi', 'body' => 'x']);

        $this->assertSame(['new-phone'], DeviceToken::pluck('token')->all());
        $log = Notification::sole();
        $this->assertSame(1, $log->sent_count);
        $this->assertSame(1, $log->failed_count);
        $this->assertSame('1 delivered, 1 failed', NotificationResource::result($log));
    }

    public function test_nobody_with_the_app_gives_a_warning_and_sends_nothing(): void
    {
        $this->firebase();
        $this->panel();
        $sami = $this->customer('Sami');

        Livewire::test(ListNotifications::class)
            ->callAction('send', data: ['audience' => 'selected', 'users' => [$sami->id], 'title' => 'Hi', 'body' => 'x'])
            ->assertNotified('Nobody received it');

        Http::assertNothingSent();
        $this->assertSame('0 delivered, 1 no app', NotificationResource::result(Notification::sole()));
    }

    public function test_without_firebase_setup_nothing_is_sent_or_logged(): void
    {
        config(['services.firebase.credentials' => '']);
        Http::fake();
        $this->panel();

        Livewire::test(ListNotifications::class)
            ->callAction('send', data: ['audience' => 'all', 'title' => 'Hi', 'body' => 'x'])
            ->assertNotified('Notification not sent');

        Http::assertNothingSent();
        $this->assertSame(0, Notification::count());
    }

    public function test_notifications_page_lists_history_including_old_rows(): void
    {
        $this->panel();
        // a row from before this update (no audience column filled)
        $old = Notification::create(['topic' => 'all_users', 'title' => 'Time to check your car', 'body' => 'old', 'response' => ['name' => 'x']]);
        $failed = Notification::create(['topic' => 'all_users', 'title' => 'Broken', 'body' => 'b', 'response' => ['error' => ['status' => 'X']]]);

        $this->get(NotificationResource::getUrl('index'))->assertOk()->assertSee('Send notification');

        Livewire::test(ListNotifications::class)->assertCanSeeTableRecords([$old, $failed]);
        $this->assertSame('All customers', NotificationResource::audienceLabel($old));
        $this->assertSame('Sent', NotificationResource::result($old));
        $this->assertSame('Failed', NotificationResource::result($failed));

        // rows saved by the old /send-notification endpoint (answer stored as JSON text)
        $oldApi = Notification::create(['topic' => 'all_users', 'title' => 'x', 'body' => 'y', 'response' => json_encode(['name' => 'projects/x/messages/1'])]);
        $this->assertSame('Sent', NotificationResource::result($oldApi->fresh()));
        $this->assertSame('Unknown', NotificationResource::result(Notification::create(['topic' => 'all_users', 'title' => 'x', 'body' => 'y'])));
    }

    public function test_login_page_says_jfeid_not_laravel(): void
    {
        config(['app.name' => 'Laravel']); // what the live server has

        $this->get('/Jfeid/login')
            ->assertOk()
            ->assertSee('Jfeid')
            ->assertDontSee('Laravel');
    }

    public function test_dashboard_has_the_send_notification_icon_that_opens_the_form(): void
    {
        $this->panel();
        $url = NotificationResource::getUrl('index', ['action' => 'send']);

        $this->get('/Jfeid')
            ->assertOk()
            ->assertSee('Send notification')
            ->assertSee(e($url), false);

        // the link opens the page, which opens the send form as soon as it loads
        Livewire::withQueryParams(['action' => 'send'])
            ->test(ListNotifications::class)
            ->assertSet('defaultAction', 'send')
            ->assertSeeHtml("mountAction('send')")
            ->call('mountAction', 'send')
            ->assertActionMounted('send');
    }

    public function test_customers_cannot_open_the_notifications_page(): void
    {
        $this->actingAs($this->customer('Maryam'));

        $this->get(NotificationResource::getUrl('index'))->assertForbidden();
    }

    // ---- dashboard: Users page bulk action (used to go to everyone) ----

    public function test_users_bulk_send_goes_only_to_the_selected_rows(): void
    {
        $this->firebase();
        $this->panel();
        $maryam = $this->customer('Maryam', ['maryam-phone']);
        $ali = $this->customer('Ali', ['ali-phone']);

        Livewire::test(ListUsers::class)
            ->callTableBulkAction('send-notification', [$maryam], data: ['title' => 'Hi Maryam', 'body' => 'x'])
            ->assertHasNoTableBulkActionErrors();

        $this->assertSame(['maryam-phone'], $this->sentTargets());
        $this->assertNotContains('topic:all_users', $this->sentTargets());
        $this->assertSame([$maryam->id], Notification::sole()->recipients);
    }

    public function test_users_page_shows_who_has_the_app(): void
    {
        $this->panel();
        $maryam = $this->customer('Maryam', ['maryam-phone']);
        $sami = $this->customer('Sami');

        Livewire::test(ListUsers::class)
            ->assertTableColumnStateSet('device_tokens_exists', true, $maryam)
            ->assertTableColumnStateSet('device_tokens_exists', false, $sami);
    }

    // ---- Users page: when did the customer last use the app ----

    private function withActivity(User $user): User
    {
        return User::query()->withAppActivity()->findOrFail($user->id);
    }

    public function test_last_in_app_comes_from_sign_in_and_signed_in_requests(): void
    {
        $this->travelTo('2026-11-01 09:00:00');
        $maryam = $this->customer('Maryam');
        $this->postJson('/api/signin', ['name_or_email' => 'maryam@example.com', 'password' => 'secret123'])->assertOk();

        $this->assertSame('2026-11-01 09:00:00', $this->withActivity($maryam)->lastSeenInApp()->toDateTimeString());

        // a signed-in request later (profile) moves it forward
        $token = $maryam->tokens()->first();
        $this->travelTo('2026-11-05 12:30:00');
        $token->forceFill(['last_used_at' => now()])->save(); // what Sanctum does on each signed-in request
        $this->assertSame('2026-11-05 12:30:00', $this->withActivity($maryam)->lastSeenInApp()->toDateTimeString());

        // never signed in
        $this->assertNull($this->withActivity($this->customer('Sami'))->lastSeenInApp());
    }

    public function test_real_signed_in_api_call_updates_last_in_app(): void
    {
        $maryam = $this->customer('Maryam');
        $this->travelTo('2026-11-01 09:00:00');
        $plain = $maryam->createToken('authToken')->plainTextToken;

        $this->travelTo('2026-11-10 08:00:00');
        $this->withToken($plain)->getJson('/api/profile')->assertOk();

        $this->assertSame('2026-11-10 08:00:00', $this->withActivity($maryam)->lastSeenInApp()->toDateTimeString());
    }

    public function test_app_opening_with_new_version_is_recorded_and_sending_does_not_change_it(): void
    {
        $this->firebase();
        $maryam = $this->customer('Maryam');
        Sanctum::actingAs($maryam);

        $this->travelTo('2026-11-20 10:00:00');
        $this->postJson('/api/device-tokens', ['token' => 'maryam-phone'])->assertOk();
        $this->travelTo('2026-11-21 18:00:00');
        $this->postJson('/api/device-tokens', ['token' => 'maryam-phone'])->assertOk(); // opened again

        $this->assertSame('2026-11-21 18:00:00', (string) $this->withActivity($maryam)->app_last_opened);

        // a notification sent later must not look like the customer opened the app
        $this->travelTo('2026-11-25 10:00:00');
        $this->panel();
        Livewire::test(ListNotifications::class)
            ->callAction('send', data: ['audience' => 'selected', 'users' => [$maryam->id], 'title' => 'Hi', 'body' => 'x'])
            ->assertNotified('Notification sent');

        $this->assertSame('2026-11-21 18:00:00', (string) $this->withActivity($maryam)->app_last_opened);
        $this->assertSame('2026-11-21 18:00:00', $this->withActivity($maryam)->lastSeenInApp()->toDateTimeString());
    }

    public function test_users_page_shows_last_in_app_and_never(): void
    {
        $this->panel();
        $maryam = $this->customer('Maryam', ['maryam-phone']);
        $maryam->createToken('authToken');
        $sami = $this->customer('Sami');

        $this->get(UserResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Last in app')
            ->assertSee('Never');

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$maryam, $sami])
            ->assertTableColumnStateSet('device_tokens_exists', true, $maryam)
            ->assertTableColumnStateSet('device_tokens_exists', false, $sami)
            ->assertTableColumnStateSet('last_seen_in_app', null, $sami);
    }

    public function test_deleting_a_customer_removes_their_phones(): void
    {
        $maryam = $this->customer('Maryam', ['maryam-phone']);

        $maryam->delete();

        $this->assertSame(0, DeviceToken::count());
    }
}

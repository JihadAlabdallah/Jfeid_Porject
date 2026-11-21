<?php

namespace Tests\Feature;

use App\Models\Policy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Public website: home (marketing), support, privacy policy and terms (the links given to Apple and Google). */
class PolicyPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.name' => 'Laravel']); // what the live server has
    }

    public function test_privacy_policy_page_shows_the_dashboard_text_centred_with_jfeid_footer(): void
    {
        Policy::create([
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => '<h2>1. Who we are</h2><ul><li>Name</li></ul>',
        ]);

        $this->get('/policies/privacy-policy')
            ->assertOk()
            ->assertSee('<title>Privacy Policy | Jfeid</title>', false)
            ->assertSee('<h2>1. Who we are</h2>', false)
            ->assertSee('plugins=typography', false)
            ->assertSee('max-w-none', false)
            ->assertSee('Jfeid. All rights reserved.')
            ->assertSee(route('site.support'), false)
            ->assertDontSee('Laravel');
    }

    public function test_missing_policy_is_not_found_and_other_slugs_are_rejected(): void
    {
        $this->get('/policies/privacy-policy')->assertNotFound();
        $this->get('/policies/anything-else')->assertNotFound();
    }

    public function test_home_page_is_the_jfeid_marketing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Your car’s service history', false)
            ->assertSee('What you get')
            ->assertSee(route('policy.show', 'privacy-policy'), false)
            ->assertSee(route('policy.show', 'terms-conditions'), false)
            ->assertDontSee('Laravel')
            ->assertDontSee('Download on the App Store'); // no store links set yet
    }

    public function test_support_page_shows_contact_details_from_settings(): void
    {
        $this->get('/support')
            ->assertOk()
            ->assertSee('How do I delete my account?')
            ->assertSee('Visit us at the Jfeid workshop.')
            ->assertDontSee('Laravel');

        config(['jfeid.support_email' => 'help@example.com', 'jfeid.support_phone' => '+961 70 000 000']);

        $this->get('/support')
            ->assertOk()
            ->assertSee('mailto:help@example.com', false)
            ->assertSee('https://wa.me/96170000000', false)
            ->assertDontSee('Visit us at the Jfeid workshop.');
    }

    public function test_store_buttons_appear_once_links_are_set(): void
    {
        config(['jfeid.app_store_url' => 'https://apps.apple.com/app/id123', 'jfeid.play_store_url' => 'https://play.google.com/store/apps/details?id=x']);

        $this->get('/')
            ->assertSee('Download on the App Store')
            ->assertSee('https://apps.apple.com/app/id123', false)
            ->assertSee('Get it on Google Play');
    }
}

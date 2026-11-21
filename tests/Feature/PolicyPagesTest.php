<?php

namespace Tests\Feature;

use App\Models\Policy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Public privacy policy / terms pages (the links given to Apple and Google). */
class PolicyPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_page_shows_the_dashboard_text_with_jfeid_footer(): void
    {
        config(['app.name' => 'Laravel']); // what the live server has

        Policy::create([
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => '<h2>1. Who we are</h2><ul><li>Name</li></ul>',
        ]);

        $this->get('/policies/privacy-policy')
            ->assertOk()
            ->assertSee('<title>Privacy Policy</title>', false)
            ->assertSee('<h2>1. Who we are</h2>', false)
            ->assertSee('plugins=typography', false)
            ->assertSee('Jfeid. All rights reserved.')
            ->assertDontSee('Laravel');
    }

    public function test_missing_policy_is_not_found_and_other_slugs_are_rejected(): void
    {
        $this->get('/policies/privacy-policy')->assertNotFound();
        $this->get('/policies/anything-else')->assertNotFound();
    }
}

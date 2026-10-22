<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_home_page_renders_content_and_clinic_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->has('content.hero.title', 3)
                ->has('content.trust.items', 3)
                ->has('content.treatments.items', 4)
                ->where('content.treatments.items.0.href', '/zabiegi/twarz')
                ->has('content.effects.items', 3)
                ->where('clinic.phone_e164', '+48797745224')
                ->where('clinic.email', 'gabinet@drzwidopiekna.pl'));
    }

    public function test_home_page_ships_seo_in_the_html(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertSee('<title inertia>Drzwi do Piękna | Medycyna estetyczna Łódź</title>', false);
        $response->assertSee('rel="canonical"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('MedicalBusiness', false);
        $response->assertSee('"telephone":"+48797745224"', false);
        $response->assertDontSee('<meta name="robots"', false);
    }

    public function test_home_page_preloads_the_lcp_image(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('rel="preload" as="image"', false)
            ->assertSee('fetchpriority="high"', false);
    }

    public function test_unknown_pages_are_404(): void
    {
        $this->get('/nie-ma-takiej-strony')->assertNotFound();
    }

    public function test_robots_blocks_everything_outside_production(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /');
    }
}

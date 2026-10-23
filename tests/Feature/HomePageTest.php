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

    public function test_home_page_head_script_seeds_the_intro_light(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Ziarno układu światła: losowe przy każdym odtworzeniu, ?intro=N wymusza ziarno N, a nieparzyste odbija światło (CSS czyta data-intro-flip).
        $this->assertStringContainsString('Math.random() * 4294967296', $html);
        $this->assertStringContainsString('intro=(\d+)', $html);
        $this->assertStringContainsString("d.setAttribute('data-intro-seed', seed)", $html);
        $this->assertStringContainsString("if (seed % 2) { d.setAttribute('data-intro-flip', ''); }", $html);

        // Płyta tła (zdjęcie ściany): numer z ziarna i preload, a pliki wszystkich płyt muszą istnieć.
        $this->assertStringContainsString("d.setAttribute('data-intro-plate', plate)", $html);
        $this->assertStringContainsString('var plates = '.config('clinic.intro_plates').';', $html);
        $this->assertStringContainsString("preload.href = base + '/plate-' + plate + '.webp'", $html);
    }

    public function test_every_intro_plate_exists_as_a_small_image(): void
    {
        $plates = (int) config('clinic.intro_plates');

        $this->assertGreaterThan(0, $plates);

        for ($i = 0; $i < $plates; $i++) {
            $path = public_path("intro/plate-{$i}.webp");

            $this->assertFileExists($path);
            // Płyta jest wczytywana przy pierwszej wizycie, więc ma być lekka (tu z zapasem: do 400 KB).
            $this->assertLessThan(400 * 1024, filesize($path));
        }

        // Tabela płyt w scenie WebGL (punkt zainteresowania dla wąskich ekranów) ma tyle wpisów, ile jest plików.
        $js = file_get_contents(resource_path('js/intro-light.js'));
        preg_match('/const PLATES = \[(.*?)\n\];/s', $js, $match);
        $this->assertSame($plates, substr_count($match[1] ?? '', 'focus:'));
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

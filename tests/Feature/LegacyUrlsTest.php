<?php

namespace Tests\Feature;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Migracja z WordPressa: każdy adres, który stara strona miała w mapach (tests/fixtures/old-site-paths.txt),
 * ma kończyć na działającej stronie nie później niż po jednym przekierowaniu 301.
 */
class LegacyUrlsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /**
     * Żądanie przez jądro HTTP, z zachowaniem ukośnika końcowego. Klient testowy Laravela (`$this->get()`)
     * obcina go z adresu w `url()`, więc nie nadaje się do sprawdzania adresów starej strony.
     */
    private function visit(string $path, string $method = 'GET'): TestResponse
    {
        $request = Request::create('http://localhost'.$path, $method);
        $response = $this->app->make(Kernel::class)->handle($request);

        return $this->createTestResponse($response, $request);
    }

    /** @return array<string, array{0: string}> */
    public static function oldPaths(): array
    {
        $paths = file(__DIR__.'/../fixtures/old-site-paths.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        return collect($paths)->mapWithKeys(fn (string $path) => [$path => [$path]])->all();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('oldPaths')]
    public function test_every_old_url_ends_on_a_working_page_within_one_redirect(string $path): void
    {
        $response = $this->visit($path);

        // Poza stroną główną adres z ukośnikiem końcowym zawsze przekierowuje, nigdy nie jest drugą kopią strony.
        if ($path === '/' || (! str_ends_with($path, '/') && $response->getStatusCode() === 200)) {
            $response->assertOk();

            return;
        }

        $response->assertStatus(301);

        $target = parse_url($response->headers->get('Location'));
        $next = $target['path'].(isset($target['query']) ? '?'.$target['query'] : '');

        $this->get($next)->assertOk();
    }

    public function test_trailing_slash_redirects_keep_the_query_string(): void
    {
        $this->visit('/cennik/')->assertStatus(301)->assertRedirect('/cennik');
        $this->visit('/cennik/?utm_source=google')->assertStatus(301)->assertRedirect('/cennik?utm_source=google');
        $this->visit('/blog/')->assertStatus(301)->assertRedirect('/blog');
        $this->visit('/blog/filtry-przeciwsloneczne/')->assertStatus(301)->assertRedirect('/blog/filtry-przeciwsloneczne');
    }

    public function test_old_urls_that_redirect_anyway_take_a_single_hop(): void
    {
        $this->visit('/zabieg/clearlift-laserowe-odmladzanie/')->assertStatus(301)->assertRedirect('/zabiegi/twarz/clearlift');
        $this->visit('/faq/')->assertStatus(301)->assertRedirect('/kontakt');
        $this->visit('/o-firmie/nasz-zespol/dorota/')->assertStatus(301)->assertRedirect('/o-mnie#zespol');
        $this->visit('/zabieg/')->assertStatus(301)->assertRedirect('/zabiegi');
    }

    public function test_treatments_without_a_page_of_their_own_go_to_the_nearest_list(): void
    {
        $this->visit('/zabieg/masaze/')->assertStatus(301)->assertRedirect('/zabiegi/cialo');
        $this->visit('/zabieg/retibooster/')->assertStatus(301)->assertRedirect('/zabiegi/szyja-i-dekolt/retibooster-therapy');
        $this->visit('/zabieg/nie-ma-takiego/')->assertNotFound();
    }

    public function test_english_pages_go_to_their_polish_counterparts(): void
    {
        $this->visit('/en/pricing/')->assertStatus(301)->assertRedirect('/cennik');
        $this->visit('/en/blog-en/')->assertStatus(301)->assertRedirect('/blog');
        $this->visit('/en/zabieg/injection-mesotherapy/')->assertStatus(301)->assertRedirect('/zabiegi/twarz/mezoterapia-iniekcyjna');
        $this->visit('/en/zabieg/injection-lipolysis/')->assertStatus(301)->assertRedirect('/zabiegi/cialo');
        $this->visit('/en/zabieg/nie-ma-takiego/')->assertNotFound();
    }

    public function test_old_wordpress_sitemaps_point_to_the_new_one(): void
    {
        foreach (['/sitemap_index.xml', '/wp-sitemap.xml', '/page-sitemap.xml', '/zabiegi-sitemap.xml'] as $old) {
            $this->get($old)->assertStatus(301)->assertRedirect('/sitemap.xml');
        }
    }

    public function test_only_safe_requests_are_redirected_for_the_trailing_slash(): void
    {
        // Wysyłka formularza pod adresem z ukośnikiem nie może zamienić się w przekierowanie GET.
        $this->visit('/kontakt/', 'POST')->assertStatus(302)->assertRedirect()->assertSessionHasErrors();
    }

    public function test_the_home_page_is_not_redirected_and_unknown_pages_are_404(): void
    {
        $this->get('/')->assertOk();
        $this->visit('/nie-ma-takiej-strony/')->assertStatus(301)->assertRedirect('/nie-ma-takiej-strony');
        $this->get('/nie-ma-takiej-strony')->assertNotFound();
    }
}

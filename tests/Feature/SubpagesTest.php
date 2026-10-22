<?php

namespace Tests\Feature;

use App\Models\Treatment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubpagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_about_page_lists_only_published_team_members(): void
    {
        $this->get('/o-mnie')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('About')
                ->has('team', 2)
                ->where('team.0.name', 'dr Anita Symonowicz')
                ->where('seo.title', 'O mnie, lek. Anita Symonowicz | Drzwi do Piękna'));
    }

    public function test_treatments_index_groups_treatments_by_area(): void
    {
        $this->get('/zabiegi')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Treatments/Index')
                ->has('categories', 5)
                ->where('categories.0.slug', 'twarz')
                ->has('categories.0.treatments'));
    }

    public function test_category_and_treatment_pages_render_with_structured_data(): void
    {
        $this->get('/zabiegi/twarz')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Treatments/Category')->where('category.name', 'Twarz'));

        $response = $this->get('/zabiegi/twarz/clearlift')->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Treatments/Show')
            ->where('treatment.name', 'ClearLift, laserowe odmładzanie')
            ->has('treatment.indications', 4)
            ->has('treatment.paragraphs', 2));
        $response->assertSee('"@type":"Service"', false);
        $response->assertSee('BreadcrumbList', false);
    }

    public function test_treatment_under_the_wrong_category_is_404(): void
    {
        $this->get('/zabiegi/usta/clearlift')->assertNotFound();
        $this->get('/zabiegi/nie-ma')->assertNotFound();
    }

    public function test_unpublished_treatments_are_hidden_everywhere(): void
    {
        Treatment::where('slug', 'clearlift')->update(['is_published' => false]);

        $this->get('/zabiegi/twarz/clearlift')->assertNotFound();
        $this->get('/zabieg/clearlift-laserowe-odmladzanie')->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('/zabiegi/twarz/clearlift', false);
    }

    public function test_price_list_comes_from_the_database(): void
    {
        $this->get('/cennik')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Prices')
                ->has('groups', 19)
                ->where('groups.0.slug', 'virtue-rf')
                ->where('groups.0.sections.0.items.0.name', 'Twarz')
                ->where('groups.0.sections.0.items.0.min', 1250));
    }

    public function test_effects_page_renders(): void
    {
        $this->get('/efekty')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Effects')->has('content.items', 3));
    }

    public function test_sitemap_lists_pages_and_published_treatments(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.url('/cennik').'</loc>', false)
            ->assertSee('<loc>'.url('/zabiegi/twarz').'</loc>', false)
            ->assertSee('<loc>'.url('/zabiegi/twarz/clearlift').'</loc>', false);
    }

    public function test_old_wordpress_urls_redirect_permanently(): void
    {
        $this->get('/zabieg/clearlift-laserowe-odmladzanie')->assertRedirect('/zabiegi/twarz/clearlift')->assertStatus(301);
        $this->get('/zabieg/clearlift-laserowe-odmladzanie/')->assertRedirect('/zabiegi/twarz/clearlift')->assertStatus(301);
        $this->get('/zabieg/peptiglow-peelboost')->assertRedirect('/zabiegi/jakosc-skory')->assertStatus(301);
        $this->get('/zabieg/nie-ma-takiego')->assertNotFound();
        $this->get('/oferta')->assertRedirect('/zabiegi')->assertStatus(301);
        $this->get('/o-firmie/nasz-zespol/dr-katya')->assertRedirect('/o-mnie#zespol')->assertStatus(301);
        $this->get('/filozofia')->assertRedirect('/o-mnie#filozofia')->assertStatus(301);
    }
}

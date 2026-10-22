<?php

namespace Tests\Feature;

use App\Models\ContactRequest;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegalPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function approve(array $attributes = []): Page
    {
        $page = Page::where('slug', Page::PRIVACY)->firstOrFail();
        $page->update([
            'body' => '<h2>Administrator</h2><p>Gabinet Drzwi do Piękna.</p><h2>Cookies i podobne technologie</h2><p>Tylko niezbędne.</p>',
            'needs_review' => false,
            'effective_from' => '2026-11-01',
            ...$attributes,
        ]);

        return $page->fresh();
    }

    public function test_the_policy_is_published_as_a_draft_until_a_lawyer_approves_it(): void
    {
        $this->get('/polityka-prywatnosci')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Legal/Show')
                ->where('page.title', 'Polityka prywatności i plików cookies')
                ->where('page.draft', true)
                ->where('page.effective_label', null)
                ->where('content.draft_title', 'Wersja robocza.')
                ->etc());

        $this->get('/sitemap.xml')->assertDontSee('/polityka-prywatnosci', false);
    }

    public function test_the_policy_skeleton_covers_what_gdpr_information_needs(): void
    {
        $sections = collect(Page::where('slug', Page::PRIVACY)->firstOrFail()->content()['toc'])->pluck('title');

        foreach ([
            'Kto jest administratorem Twoich danych',
            'Jakie dane przetwarzamy i w jakim celu',
            'Komu przekazujemy dane',
            'Jak długo przechowujemy dane',
            'Twoje prawa',
            'Skarga do organu nadzorczego',
            'Czy musisz podać dane',
            'Cookies i podobne technologie',
        ] as $required) {
            $this->assertContains($required, $sections->all());
        }
    }

    public function test_headings_get_unique_anchors_and_the_cookie_section_has_a_stable_one(): void
    {
        $content = Page::where('slug', Page::PRIVACY)->firstOrFail()->content();
        $ids = array_column($content['toc'], 'id');

        $this->assertSame($ids, array_values(array_unique($ids)));
        $this->assertContains('cookies', $ids);
        $this->assertStringContainsString('<h2 id="cookies">', $content['html']);
    }

    public function test_the_cookies_in_the_policy_are_exactly_the_ones_a_visitor_gets(): void
    {
        $set = collect($this->get('/kontakt')->baseResponse->headers->getCookies())->map->getName()->all();
        $html = Page::where('slug', Page::PRIVACY)->firstOrFail()->content()['html'];

        // Każdy plik, który strona ustawia odwiedzającemu, jest w tabeli cookies. Jeśli ktoś doda nowy
        // (analityka, narzędzie zewnętrzne), ten test zmusi do aktualizacji dokumentu.
        $this->assertEqualsCanonicalizing([config('session.cookie'), 'XSRF-TOKEN'], $set);

        foreach ($set as $name) {
            $this->assertStringContainsString("<code>{$name}</code>", $html);
        }

        // Czas ważności z tekstu ("2 godziny") to ustawienie sesji.
        $this->assertSame(120, config('session.lifetime'));
        $this->assertStringContainsString('2 godziny', $html);

        // Wpis w sessionStorage animacji powitalnej.
        $this->assertStringContainsString("'ddp-intro'", file_get_contents(resource_path('js/components/Intro.vue')));
        $this->assertStringContainsString('<code>ddp-intro</code>', $html);
    }

    public function test_the_policy_claims_about_data_handling_hold_for_the_code(): void
    {
        // "Nie zapisujemy adresu IP razem z zapytaniem."
        $columns = collect(Schema::getColumnListing((new ContactRequest)->getTable()));
        $this->assertFalse($columns->contains(fn (string $c) => str_contains($c, 'ip')), 'Zapytanie nie może mieć kolumny z adresem IP.');

        // "Nie używamy plików analitycznych, reklamowych ani śledzących i nie osadzamy zewnętrznych narzędzi."
        $layout = file_get_contents(resource_path('views/app.blade.php'));
        foreach (['googletagmanager', 'google-analytics', 'fonts.googleapis', 'facebook.net', 'hotjar'] as $tracker) {
            $this->assertStringNotContainsString($tracker, $layout);
        }
    }

    public function test_placeholders_are_detected_but_footnotes_and_numbers_are_not(): void
    {
        $this->assertSame(['[NIP]', '[okres przechowywania]'], Page::placeholdersIn('<p>NIP [NIP], okres [okres przechowywania], przypis [1], [12 miesięcy].</p>'));
        $this->assertSame([], Page::placeholdersIn('<p>Zwykły tekst bez pól.</p>'));
        $this->assertSame([], Page::placeholdersIn(null));
    }

    public function test_an_approved_policy_loses_the_draft_notice_and_gets_indexed(): void
    {
        $this->approve();

        $this->get('/polityka-prywatnosci')
            ->assertOk()
            ->assertDontSee('<meta name="robots"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('page.draft', false)
                ->where('page.effective_label', '1 listopada 2026')
                ->etc());

        $this->get('/sitemap.xml')->assertSee('/polityka-prywatnosci', false);
    }

    public function test_leftover_fields_keep_the_page_a_draft_even_when_marked_as_approved(): void
    {
        $this->approve(['body' => '<h2>Administrator</h2><p>NIP [NIP]</p>']);

        $this->get('/polityka-prywatnosci')
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertInertia(fn (Assert $page) => $page->where('page.draft', true)->etc());
    }

    public function test_the_policy_html_is_sanitised(): void
    {
        $this->approve(['body' => '<h2>Tytuł</h2><script>alert(1)</script><p onclick="x()">Treść <a href="javascript:alert(1)">link</a></p>']);

        $this->get('/polityka-prywatnosci')->assertInertia(fn (Assert $page) => $page
            ->where('page.html', fn ($html) => ! str_contains($html, '<script') && ! str_contains($html, 'onclick') && ! str_contains($html, 'javascript:'))
            ->etc());
    }

    public function test_a_missing_document_is_a_404(): void
    {
        Page::query()->delete();

        $this->get('/polityka-prywatnosci')->assertNotFound();
    }

    public function test_the_consent_in_the_contact_form_links_to_the_policy(): void
    {
        $this->get('/kontakt')->assertInertia(fn (Assert $page) => $page
            ->where('content.form.consent.link', 'polityce prywatności')
            ->has('content.form.consent.before')
            ->has('content.form.consent.after')
            ->where('clinic.legal.privacy', route('privacy', absolute: false))
            ->etc());

        $this->get(config('clinic.legal.privacy'))->assertOk();
        $this->assertStringContainsString('clinic.legal.privacy', file_get_contents(resource_path('js/pages/Contact.vue')));
    }

    public function test_the_footer_links_both_documents(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('clinic.legal.privacy', '/polityka-prywatnosci')
            ->where('clinic.legal.cookies', '/polityka-prywatnosci#cookies')
            ->etc());

        $footer = file_get_contents(resource_path('js/components/SiteFooter.vue'));
        $this->assertStringContainsString('clinic.legal.privacy', $footer);
        $this->assertStringContainsString('clinic.legal.cookies', $footer);
    }

    public function test_the_legal_check_blocks_a_draft_and_passes_an_approved_document(): void
    {
        $this->artisan('legal:check')->assertExitCode(1);

        $this->approve(['body' => '<h2>Administrator</h2><p>Gabinet.</p>']);
        // Zgoda w formularzu ma jeszcze pole "do zatwierdzenia" w lang/pl/pages.php.
        $this->artisan('legal:check')->assertExitCode(1);

        trans('pages.contact.form.consent');
        app('translator')->addLines(['pages.contact.form.consent.after' => '.'], 'pl');

        $this->artisan('legal:check')->assertSuccessful();
    }

    public function test_the_legal_check_reports_a_missing_policy_and_a_missing_date(): void
    {
        Page::query()->delete();
        $this->artisan('legal:check')->assertExitCode(1);

        Page::create(['slug' => Page::PRIVACY, 'title' => 'Polityka', 'body' => '<p>Gotowe.</p>', 'needs_review' => false, 'effective_from' => null]);
        trans('pages.contact.form.consent');
        app('translator')->addLines(['pages.contact.form.consent.after' => '.'], 'pl');

        $this->artisan('legal:check')->assertExitCode(1);
    }
}

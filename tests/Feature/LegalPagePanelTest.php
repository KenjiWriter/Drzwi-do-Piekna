<?php

namespace Tests\Feature;

use App\Filament\Resources\LegalPages\LegalPageResource;
use App\Filament\Resources\LegalPages\Pages\EditLegalPage;
use App\Filament\Resources\LegalPages\Pages\ListLegalPages;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LegalPagePanelTest extends TestCase
{
    use RefreshDatabase;

    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->actingAs(User::factory()->create());
        $this->page = Page::where('slug', Page::PRIVACY)->firstOrFail();
    }

    public function test_the_documents_pages_load_and_show_the_draft_state(): void
    {
        $this->get('/admin/legal-pages')->assertOk()->assertSee('Wersja robocza');
        $this->get("/admin/legal-pages/{$this->page->id}/edit")->assertOk();

        Livewire::test(ListLegalPages::class)->assertCanSeeTableRecords([$this->page]);
    }

    public function test_documents_cannot_be_created_or_deleted_because_their_address_is_fixed(): void
    {
        $this->assertFalse(LegalPageResource::canCreate());
        $this->assertFalse(LegalPageResource::canDelete($this->page));
        $this->get('/admin/legal-pages/create')->assertNotFound();
    }

    public function test_the_text_can_be_edited_and_saved(): void
    {
        Livewire::test(EditLegalPage::class, ['record' => $this->page->id])
            ->fillForm(['title' => 'Polityka prywatności', 'body' => '<h2>Nowa sekcja</h2><p>Treść od prawnika.</p>'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Polityka prywatności', $this->page->fresh()->title);
        $this->assertStringContainsString('Treść od prawnika.', $this->page->fresh()->body);
    }

    public function test_a_document_with_leftover_fields_cannot_be_marked_as_approved(): void
    {
        Livewire::test(EditLegalPage::class, ['record' => $this->page->id])
            ->fillForm(['needs_review' => false, 'effective_from' => '2026-11-01'])
            ->call('save')
            ->assertHasFormErrors(['needs_review']);

        $this->assertTrue($this->page->fresh()->needs_review);
    }

    public function test_an_approved_document_needs_an_effective_date(): void
    {
        Livewire::test(EditLegalPage::class, ['record' => $this->page->id])
            ->fillForm(['body' => '<h2>Administrator</h2><p>Gabinet.</p>', 'needs_review' => false, 'effective_from' => null])
            ->call('save')
            ->assertHasFormErrors(['effective_from' => 'required']);
    }

    public function test_a_complete_document_can_be_approved_and_then_goes_live(): void
    {
        Livewire::test(EditLegalPage::class, ['record' => $this->page->id])
            ->fillForm(['body' => '<h2>Administrator</h2><p>Gabinet.</p>', 'needs_review' => false, 'effective_from' => '2026-11-01'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($this->page->fresh()->isDraft());
        $this->get('/polityka-prywatnosci')->assertDontSee('<meta name="robots"', false);
    }

    public function test_it_can_go_back_to_a_draft_without_restrictions(): void
    {
        $this->page->update(['body' => '<p>Gotowe.</p>', 'needs_review' => false, 'effective_from' => '2026-11-01']);

        Livewire::test(EditLegalPage::class, ['record' => $this->page->id])
            ->fillForm(['needs_review' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($this->page->fresh()->isDraft());
    }
}

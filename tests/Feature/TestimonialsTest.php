<?php

namespace Tests\Feature;

use App\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use App\Filament\Resources\Testimonials\Pages\EditTestimonial;
use App\Filament\Resources\Testimonials\Pages\ListTestimonials;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class TestimonialsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function real(array $attributes = []): Testimonial
    {
        return Testimonial::create([
            'author' => 'Maria K.',
            'quote' => 'Spokojna wizyta i naturalny efekt.',
            'source' => 'ZnanyLekarz',
            'consent_confirmed' => true,
            'is_published' => true,
            ...$attributes,
        ]);
    }

    public function test_the_demo_opinion_is_shown_with_a_note_about_being_an_example(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->has('content.reviews.items', 1)
            ->where('content.reviews.items.0.author', 'Anna')
            ->where('content.reviews.items.0.quote', fn ($quote) => ! str_contains($quote, '„'))
            ->where('content.reviews.note', fn ($note) => str_contains($note, 'Przykładowa opinia'))
            ->etc());
    }

    public function test_the_reviews_section_disappears_when_nothing_is_published(): void
    {
        Testimonial::query()->delete();

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->where('content.reviews', null));

        $this->real(['is_published' => false]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('content.reviews', null));
    }

    public function test_a_real_opinion_needs_consent_to_appear_even_when_published(): void
    {
        Testimonial::query()->delete();
        $this->real(['author' => 'Bez zgody', 'consent_confirmed' => false]);
        $this->real(['author' => 'Ze zgodą', 'sort_order' => 2]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->has('content.reviews.items', 1)
            ->where('content.reviews.items.0.author', 'Ze zgodą')
            ->where('content.reviews.items.0.source', 'ZnanyLekarz')
            ->where('content.reviews.note', null)
            ->etc());
    }

    public function test_opinions_come_in_the_configured_order(): void
    {
        Testimonial::query()->delete();
        $this->real(['author' => 'Druga', 'sort_order' => 2]);
        $this->real(['author' => 'Pierwsza', 'sort_order' => 1]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('content.reviews.items.0.author', 'Pierwsza')
            ->where('content.reviews.items.1.author', 'Druga')
            ->etc());
    }

    public function test_staff_can_add_an_opinion_with_consent(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateTestimonial::class)
            ->fillForm([
                'author' => 'Ewa',
                'quote' => 'Dziękuję za uważną konsultację.',
                'source' => 'Google',
                'source_url' => 'https://g.page/drzwidopiekna',
                'consent_confirmed' => true,
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(Testimonial::where('author', 'Ewa')->firstOrFail()->is_published);
    }

    public function test_an_opinion_cannot_be_published_without_consent(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateTestimonial::class)
            ->fillForm(['author' => 'Ewa', 'quote' => 'Tekst.', 'consent_confirmed' => false, 'is_published' => true])
            ->call('create')
            ->assertHasFormErrors(['is_published']);

        $this->assertSame(0, Testimonial::where('author', 'Ewa')->count());
    }

    public function test_withdrawing_consent_unpublishes_the_opinion(): void
    {
        $this->actingAs(User::factory()->create());
        $opinion = $this->real();

        Livewire::test(EditTestimonial::class, ['record' => $opinion->id])
            ->assertFormSet(['is_published' => true])
            ->set('data.consent_confirmed', false)
            ->assertFormSet(['is_published' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($opinion->fresh()->is_published);
    }

    public function test_the_list_shows_opinions_and_filters_by_consent(): void
    {
        $this->actingAs(User::factory()->create());
        $withConsent = $this->real(['author' => 'Ze zgodą']);
        $without = $this->real(['author' => 'Bez zgody', 'consent_confirmed' => false, 'is_published' => false]);

        Livewire::test(ListTestimonials::class)
            ->assertCanSeeTableRecords([$withConsent, $without])
            ->filterTable('consent_confirmed', false)
            ->assertCanSeeTableRecords([$without])
            ->assertCanNotSeeTableRecords([$withConsent]);
    }

    public function test_demo_opinion_is_not_seeded_in_production(): void
    {
        Testimonial::query()->delete();
        $this->app['env'] = 'production';

        app(\Database\Seeders\TestimonialSeeder::class)->run();

        $this->assertSame(0, Testimonial::count());
    }
}

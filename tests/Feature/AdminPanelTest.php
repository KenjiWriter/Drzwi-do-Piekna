<?php

namespace Tests\Feature;

use App\Enums\ContactStatus;
use App\Filament\Resources\ContactRequests\Pages\ListContactRequests;
use App\Filament\Resources\ContactRequests\Pages\ViewContactRequest;
use App\Filament\Resources\PriceGroups\Pages\EditPriceGroup;
use App\Filament\Resources\PriceGroups\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\TreatmentCategories\Pages\ListTreatmentCategories;
use App\Filament\Resources\Treatments\Pages\CreateTreatment;
use App\Filament\Resources\Treatments\Pages\EditTreatment;
use App\Filament\Resources\Treatments\Pages\ListTreatments;
use App\Models\ContactRequest;
use App\Models\PriceGroup;
use App\Models\TeamMember;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->actingAs(User::factory()->create());
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        auth()->logout();

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/treatments')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
    }

    public function test_every_panel_page_loads_for_staff(): void
    {
        $group = PriceGroup::firstOrFail();
        $treatment = Treatment::firstOrFail();
        $contact = ContactRequest::create(['name' => 'Ola', 'phone' => '797 745 224', 'consent_at' => now()]);

        foreach (['/admin', '/admin/treatment-categories', '/admin/treatments', '/admin/price-groups', '/admin/team-members', '/admin/contact-requests',
            '/admin/treatments/create', '/admin/team-members/create', '/admin/price-groups/create',
            "/admin/treatments/{$treatment->id}/edit", "/admin/price-groups/{$group->id}/edit", "/admin/contact-requests/{$contact->id}"] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_a_treatment_can_be_created_and_shows_up_on_the_site(): void
    {
        $category = TreatmentCategory::where('slug', 'twarz')->firstOrFail();

        Livewire::test(CreateTreatment::class)
            ->fillForm([
                'treatment_category_id' => $category->id,
                'name' => 'Nowy zabieg testowy',
                'slug' => 'nowy-zabieg-testowy',
                'summary' => 'Krótki opis nowego zabiegu.',
                // Prosty Repeater trzyma w formularzu elementy ['item' => ...], a do bazy zapisuje listę tekstów.
                'indications' => [['item' => 'Pierwsze wskazanie'], ['item' => 'Drugie wskazanie']],
                'is_published' => true,
                'sort_order' => 99,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $treatment = Treatment::where('slug', 'nowy-zabieg-testowy')->firstOrFail();
        $this->assertSame(['Pierwsze wskazanie', 'Drugie wskazanie'], $treatment->indications);

        $this->get('/zabiegi/twarz/nowy-zabieg-testowy')->assertOk();
        $this->get('/sitemap.xml')->assertSee('/zabiegi/twarz/nowy-zabieg-testowy', false);
    }

    public function test_treatment_slugs_must_be_unique_and_url_safe(): void
    {
        $category = TreatmentCategory::firstOrFail();

        Livewire::test(CreateTreatment::class)
            ->fillForm(['treatment_category_id' => $category->id, 'name' => 'X', 'slug' => 'clearlift', 'summary' => 'Opis'])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);

        Livewire::test(CreateTreatment::class)
            ->fillForm(['treatment_category_id' => $category->id, 'name' => 'X', 'slug' => 'Zły Adres', 'summary' => 'Opis'])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'regex']);
    }

    public function test_unpublishing_a_treatment_hides_it_from_the_site(): void
    {
        $treatment = Treatment::where('slug', 'clearlift')->firstOrFail();
        $this->get('/zabiegi/twarz/clearlift')->assertOk();

        Livewire::test(EditTreatment::class, ['record' => $treatment->id])
            ->fillForm(['is_published' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/zabiegi/twarz/clearlift')->assertNotFound();
    }

    public function test_treatment_list_filters_by_publication(): void
    {
        Treatment::where('slug', 'clearlift')->update(['is_published' => false]);

        Livewire::test(ListTreatments::class)
            ->filterTable('is_published', false)
            ->assertCanSeeTableRecords(Treatment::where('is_published', false)->get())
            ->assertCanNotSeeTableRecords(Treatment::where('is_published', true)->get());
    }

    public function test_an_area_with_treatments_cannot_be_deleted_from_the_list(): void
    {
        $full = TreatmentCategory::where('slug', 'twarz')->firstOrFail();
        $empty = TreatmentCategory::create(['slug' => 'pusty', 'name' => 'Pusty', 'summary' => 'Opis']);

        Livewire::test(ListTreatmentCategories::class)
            ->assertActionHidden(TestAction::make('delete')->table($full))
            ->assertActionVisible(TestAction::make('delete')->table($empty));
    }

    public function test_team_member_requires_a_name_and_a_clean_slug(): void
    {
        Livewire::test(CreateTeamMember::class)
            ->fillForm(['name' => '', 'slug' => 'ok'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);

        Livewire::test(CreateTeamMember::class)
            ->fillForm(['name' => 'Jan Nowak', 'slug' => 'jan-nowak', 'role' => 'Fizjoterapeuta', 'is_published' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(TeamMember::where('slug', 'jan-nowak')->exists());
    }

    public function test_price_items_are_managed_inside_the_group(): void
    {
        $group = PriceGroup::where('slug', 'peelingi')->firstOrFail();
        $component = fn () => Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $group, 'pageClass' => EditPriceGroup::class]);

        $component()->assertCanSeeTableRecords($group->items);

        $component()
            ->callAction(TestAction::make('create')->table(), ['name' => 'Nowy peeling', 'price_min' => 400, 'price_max' => 500, 'is_from' => false])
            ->assertHasNoFormErrors();

        $item = $group->items()->where('name', 'Nowy peeling')->firstOrFail();
        $this->assertSame('400–500 zł', $item->priceLabel());

        // Zakres "do" nie może być niższy niż "od".
        $component()
            ->callAction(TestAction::make('create')->table(), ['name' => 'Błędny', 'price_min' => 500, 'price_max' => 400])
            ->assertHasFormErrors(['price_max']);
    }

    public function test_price_labels_read_naturally(): void
    {
        $group = PriceGroup::firstOrFail();
        $make = fn (array $a) => $group->items()->create(['name' => 'x', ...$a]);

        $this->assertSame('1 250 zł', $make(['price_min' => 1250])->priceLabel());
        $this->assertSame('od 450 zł', $make(['price_min' => 450, 'is_from' => true])->priceLabel());
        $this->assertSame('1 800 zł', $make(['pack_price' => 1800])->priceLabel());
        $this->assertSame('Cena po konsultacji', $make([])->priceLabel());
    }

    public function test_opening_a_new_request_marks_it_as_read_and_it_can_be_closed(): void
    {
        $contact = ContactRequest::create(['name' => 'Ola', 'phone' => '797 745 224', 'consent_at' => now()]);
        $this->assertSame(ContactStatus::New, $contact->fresh()->status);

        Livewire::test(ViewContactRequest::class, ['record' => $contact->id])
            ->assertSee('Ola')
            ->callAction('markDone');

        $this->assertSame(ContactStatus::Done, $contact->fresh()->status);
    }

    public function test_requests_can_be_filtered_and_closed_from_the_list(): void
    {
        $new = ContactRequest::create(['name' => 'Nowa', 'phone' => '797 745 224', 'consent_at' => now()]);
        $done = ContactRequest::create(['name' => 'Stara', 'phone' => '797 745 224', 'consent_at' => now(), 'status' => ContactStatus::Done]);

        Livewire::test(ListContactRequests::class)
            ->filterTable('status', ContactStatus::New->value)
            ->assertCanSeeTableRecords([$new])
            ->assertCanNotSeeTableRecords([$done])
            ->callAction(TestAction::make('markDone')->table($new));

        $this->assertSame(ContactStatus::Done, $new->fresh()->status);
    }

    public function test_the_dev_admin_account_is_never_seeded_in_production(): void
    {
        config(['clinic.admin' => ['name' => 'Dev', 'email' => 'dev@example.test', 'password' => 'haslo-testowe-123']]);

        // Bezpośrednio, bo `db:seed` w środowisku produkcyjnym prosi o potwierdzenie.
        $this->app['env'] = 'production';
        app(AdminUserSeeder::class)->run();
        $this->assertDatabaseMissing('users', ['email' => 'dev@example.test']);

        $this->app['env'] = 'testing';
        app(AdminUserSeeder::class)->run();
        $user = User::where('email', 'dev@example.test')->firstOrFail();
        $this->assertTrue(\Hash::check('haslo-testowe-123', $user->password));
    }

    public function test_robots_hides_the_panel_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('/robots.txt')->assertSee('Disallow: /admin');
    }
}

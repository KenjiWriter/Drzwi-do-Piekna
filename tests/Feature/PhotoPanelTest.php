<?php

namespace Tests\Feature;

use App\Filament\Resources\Photos\Pages\CreatePhoto;
use App\Filament\Resources\Photos\Pages\EditPhoto;
use App\Filament\Resources\Photos\Pages\ListPhotos;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\Resources\TreatmentCategories\Pages\EditTreatmentCategory;
use App\Filament\Resources\Treatments\Pages\EditTreatment;
use App\Models\Photo;
use App\Models\TeamMember;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use App\Models\User;
use App\Support\PhotoStore;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class PhotoPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Photo::DISK);
        $this->seed();
        $this->actingAs(User::factory()->create());
    }

    private function photo(string $title = 'Gabinet', string $alt = 'Wnętrze gabinetu'): Photo
    {
        // Obiekt musi żyć do końca zapisu, bo jego plik tymczasowy znika razem z nim.
        $file = UploadedFile::fake()->image('x.jpg', 1200, 800);

        return app(PhotoStore::class)->store($file->getRealPath(), $title, $alt);
    }

    public function test_photo_pages_load(): void
    {
        $photo = $this->photo();

        foreach (['/admin/photos', '/admin/photos/create', "/admin/photos/{$photo->id}/edit", '/admin/testimonials', '/admin/testimonials/create'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_a_photo_is_uploaded_and_gets_a_title_from_the_file_name(): void
    {
        Livewire::test(CreatePhoto::class)
            ->fillForm(['file' => UploadedFile::fake()->image('portret-lekarki.jpg', 1600, 1000), 'alt' => 'Lekarka w gabinecie'])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect('/admin/photos');

        $photo = Photo::firstOrFail();

        $this->assertSame('portret lekarki', $photo->title);
        $this->assertSame('Lekarka w gabinecie', $photo->alt);
        $this->assertSame([480, 960, 1440, 1600], $photo->sortedWidths());
        Storage::disk(Photo::DISK)->assertExists($photo->variantPath(960));
    }

    public function test_alt_text_is_required(): void
    {
        Livewire::test(CreatePhoto::class)
            ->fillForm(['file' => UploadedFile::fake()->image('a.jpg', 1200, 800), 'alt' => ''])
            ->call('create')
            ->assertHasFormErrors(['alt' => 'required']);

        $this->assertSame(0, Photo::count());
    }

    public function test_a_photo_that_is_too_small_is_refused_with_a_message(): void
    {
        Livewire::test(CreatePhoto::class)
            ->fillForm(['file' => UploadedFile::fake()->image('maly.jpg', 300, 300), 'alt' => 'Za małe'])
            ->call('create')
            ->assertNotified('Nie wgrano zdjęcia')
            ->assertNoRedirect();

        $this->assertSame(0, Photo::count());
        $this->assertSame([], Storage::disk(Photo::DISK)->allFiles());
    }

    public function test_title_and_alt_can_be_edited(): void
    {
        $photo = $this->photo();

        Livewire::test(EditPhoto::class, ['record' => $photo->id])
            ->fillForm(['title' => 'Nowa nazwa', 'alt' => 'Nowy opis'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['Nowa nazwa', 'Nowy opis'], [$photo->fresh()->title, $photo->fresh()->alt]);
    }

    public function test_a_photo_in_use_cannot_be_deleted_but_an_unused_one_can(): void
    {
        $used = $this->photo('Użyte');
        $unused = $this->photo('Wolne');
        TeamMember::first()->update(['photo_id' => $used->id]);

        Livewire::test(ListPhotos::class)
            ->assertActionHidden(TestAction::make('delete')->table($used))
            ->assertActionVisible(TestAction::make('delete')->table($unused))
            ->callAction(TestAction::make('delete')->table($unused));

        $this->assertModelMissing($unused);
        Storage::disk(Photo::DISK)->assertMissing($unused->variantPath(960));
        $this->assertModelExists($used);
    }

    public function test_unused_filter_lists_only_photos_nobody_uses(): void
    {
        $used = $this->photo('Użyte');
        $unused = $this->photo('Wolne');
        Treatment::first()->update(['photo_id' => $used->id]);

        Livewire::test(ListPhotos::class)
            ->filterTable('unused')
            ->assertCanSeeTableRecords([$unused])
            ->assertCanNotSeeTableRecords([$used]);
    }

    public function test_photos_can_be_picked_for_team_treatments_and_areas(): void
    {
        $photo = $this->photo();

        $member = TeamMember::first();
        Livewire::test(EditTeamMember::class, ['record' => $member->id])->fillForm(['photo_id' => $photo->id])->call('save')->assertHasNoFormErrors();

        $treatment = Treatment::first();
        Livewire::test(EditTreatment::class, ['record' => $treatment->id])->fillForm(['photo_id' => $photo->id])->call('save')->assertHasNoFormErrors();

        $category = TreatmentCategory::first();
        Livewire::test(EditTreatmentCategory::class, ['record' => $category->id])->fillForm(['photo_id' => $photo->id])->call('save')->assertHasNoFormErrors();

        $this->assertSame(3, $photo->fresh()->usageCount());
    }

    public function test_a_team_photo_replaces_the_monogram_on_the_about_page(): void
    {
        $photo = $this->photo('Portret', 'Dr Anita Symonowicz w gabinecie');
        $member = TeamMember::published()->first();
        $member->update(['photo_id' => $photo->id]);

        $this->get('/o-mnie')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('About')
                ->where('team.0.photo.alt', 'Dr Anita Symonowicz w gabinecie')
                ->where('team.0.photo.width', 960)
                ->where('team.0.photo.height', 640)
                ->etc());
    }

    public function test_a_treatment_photo_is_shown_and_used_for_link_previews(): void
    {
        $photo = $this->photo('ClearLift', 'Zabieg laserowy twarzy');
        Treatment::where('slug', 'clearlift')->update(['photo_id' => $photo->id]);

        $response = $this->get('/zabiegi/twarz/clearlift')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('treatment.photo.alt', 'Zabieg laserowy twarzy')
                ->where('treatment.photo.src', fn ($src) => str_starts_with($src, '/storage/photos/')));

        $og = url($photo->url(1440));
        $response->assertSee('<meta property="og:image" content="'.$og.'"', false);
        $response->assertSee('"image":"'.str_replace('/', '\/', $og).'"', false);
    }

    public function test_a_treatment_without_a_photo_still_renders(): void
    {
        $this->get('/zabiegi/twarz/clearlift')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('treatment.photo', null))
            ->assertDontSee('og:image', false);
    }

    public function test_area_tiles_use_the_library_photo_and_fall_back_to_the_demo_key(): void
    {
        $photo = $this->photo('Twarz', 'Kobieta po zabiegu');
        TreatmentCategory::where('slug', 'twarz')->update(['photo_id' => $photo->id]);

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('content.treatments.items.0.image.alt', 'Kobieta po zabiegu')
                ->where('content.treatments.items.1.image', 'tile-lips'));

        $this->get('/zabiegi')
            ->assertInertia(fn (Assert $page) => $page
                ->where('categories.0.image.alt', 'Kobieta po zabiegu')
                ->where('categories.1.image', 'tile-lips'));
    }
}

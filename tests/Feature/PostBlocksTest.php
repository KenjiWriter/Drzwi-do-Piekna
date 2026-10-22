<?php

namespace Tests\Feature;

use App\Filament\Resources\Photos\Pages\ListPhotos;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Models\Photo;
use App\Models\Post;
use App\Models\User;
use App\Support\PhotoStore;
use App\Support\PostContent;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\RichEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PostBlocksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Photo::DISK);
        $this->seed();
        $this->actingAs(User::factory()->create());
    }

    private function photo(string $title = 'Zdjęcie', string $alt = 'Opis zdjęcia'): Photo
    {
        $file = UploadedFile::fake()->image('x.jpg', 1200, 800);

        return app(PhotoStore::class)->store($file->getRealPath(), $title, $alt);
    }

    /** Blok tak, jak zapisuje go edytor: w bazie tylko ustawienia bloku, bez gotowego HTML-a zdjęcia. */
    private function block(string $id, array $config): string
    {
        return '<div data-type="customBlock" data-config="'.e(json_encode($config)).'" data-id="'.$id.'"></div>';
    }

    private function makePost(string $body, array $attributes = []): Post
    {
        return Post::create([
            'slug' => 'wpis-z-blokami',
            'title' => 'Wpis z blokami',
            'excerpt' => 'Zajawka.',
            'body' => $body,
            'author_name' => 'Jan Nowak',
            'is_published' => true,
            'published_at' => now()->subDay(),
            ...$attributes,
        ]);
    }

    public function test_the_photo_block_renders_a_responsive_figure_from_the_library(): void
    {
        $photo = $this->photo('Gabinet', 'Wnętrze gabinetu');
        $html = PostContent::toHtml('<p>Przed.</p>'.$this->block('photo', ['photo_id' => (string) $photo->id, 'caption' => 'Nasz gabinet', 'size' => 'narrow']));

        $this->assertStringContainsString('<figure class="ddp-figure ddp-figure--narrow">', $html);
        $this->assertStringContainsString('alt="Wnętrze gabinetu"', $html);
        $this->assertStringContainsString('srcset="', $html);
        $this->assertStringContainsString('-480.webp 480w', $html);
        $this->assertStringContainsString('width="960" height="640"', $html);
        $this->assertStringContainsString('<figcaption>Nasz gabinet</figcaption>', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
    }

    public function test_a_photo_block_without_caption_is_wide_by_default(): void
    {
        $html = PostContent::toHtml($this->block('photo', ['photo_id' => (string) $this->photo()->id]));

        $this->assertStringContainsString('ddp-figure--wide', $html);
        $this->assertStringNotContainsString('<figcaption', $html);
    }

    public function test_captions_are_escaped(): void
    {
        $html = PostContent::toHtml($this->block('photo', ['photo_id' => (string) $this->photo()->id, 'caption' => '<script>alert(1)</script> & "cudzysłów"']));

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('&amp;', $html);
    }

    public function test_the_gallery_block_puts_two_or_three_photos_in_a_row(): void
    {
        [$a, $b, $c] = [$this->photo('A', 'Zdjęcie A'), $this->photo('B', 'Zdjęcie B'), $this->photo('C', 'Zdjęcie C')];

        $two = PostContent::toHtml($this->block('gallery', ['items' => [['photo_id' => (string) $a->id, 'caption' => 'Lewe'], ['photo_id' => (string) $b->id, 'caption' => '']]]));
        $three = PostContent::toHtml($this->block('gallery', ['items' => [['photo_id' => (string) $a->id], ['photo_id' => (string) $b->id], ['photo_id' => (string) $c->id]]]));

        $this->assertStringContainsString('ddp-gallery ddp-gallery--2', $two);
        $this->assertSame(2, substr_count($two, '<figure>'));
        $this->assertStringContainsString('<figcaption>Lewe</figcaption>', $two);
        $this->assertSame(1, substr_count($two, '<figcaption>'));
        $this->assertStringContainsString('ddp-gallery--3', $three);
        $this->assertSame(3, substr_count($three, '<figure>'));
    }

    public function test_a_photo_removed_from_the_library_leaves_no_empty_frame(): void
    {
        $photo = $this->photo();
        $kept = $this->photo('Zostaje', 'Zostaje w rzędzie');
        $ids = [(string) $photo->id, (string) $kept->id];
        $photo->delete();

        $this->assertSame('', PostContent::toHtml($this->block('photo', ['photo_id' => $ids[0]])));

        $gallery = PostContent::toHtml($this->block('gallery', ['items' => [['photo_id' => $ids[0]], ['photo_id' => $ids[1]]]]));
        $this->assertSame(1, substr_count($gallery, '<figure>'));
        $this->assertStringContainsString('Zostaje w rzędzie', $gallery);
    }

    public function test_the_text_around_blocks_keeps_its_formatting(): void
    {
        $html = PostContent::toHtml('<h2>Tytuł sekcji</h2><p><strong>Pogrubione</strong> i <em>pochylone</em></p><p style="text-align: center;">Na środku</p><ul><li><p>Punkt</p></li></ul><hr>');

        $this->assertStringContainsString('<h2>Tytuł sekcji</h2>', $html);
        $this->assertStringContainsString('<strong>Pogrubione</strong>', $html);
        $this->assertStringContainsString('text-align: center', $html);
        $this->assertStringContainsString('<ul><li>', $html);
        $this->assertStringContainsString('<hr', $html);
    }

    public function test_photos_used_in_the_text_are_tracked_so_they_cannot_be_deleted(): void
    {
        $inText = $this->photo('W tekście');
        $inGallery = $this->photo('W galerii');
        $free = $this->photo('Wolne');

        $post = $this->makePost(
            $this->block('photo', ['photo_id' => (string) $inText->id])
            .$this->block('gallery', ['items' => [['photo_id' => (string) $inGallery->id], ['photo_id' => '999999']]])
        );

        $this->assertEqualsCanonicalizing([$inText->id, $inGallery->id], $post->photos()->pluck('photos.id')->all());
        $this->assertSame(1, $inText->fresh()->usageCount());
        $this->assertSame(0, $free->usageCount());

        Livewire::test(ListPhotos::class)
            ->assertActionHidden(TestAction::make('delete')->table($inText))
            ->assertActionHidden(TestAction::make('delete')->table($inGallery))
            ->assertActionVisible(TestAction::make('delete')->table($free))
            ->filterTable('unused')
            ->assertCanSeeTableRecords([$free])
            ->assertCanNotSeeTableRecords([$inText, $inGallery]);
    }

    public function test_removing_the_block_frees_the_photo_again(): void
    {
        $photo = $this->photo();
        $post = $this->makePost($this->block('photo', ['photo_id' => (string) $photo->id]));
        $this->assertTrue($photo->fresh()->isUsed());

        $post->update(['body' => '<p>Bez zdjęcia.</p>']);

        $this->assertFalse($photo->fresh()->isUsed());
    }

    public function test_deleting_a_post_frees_its_photos(): void
    {
        $photo = $this->photo();
        $post = $this->makePost($this->block('photo', ['photo_id' => (string) $photo->id]));

        $post->delete();

        $this->assertFalse($photo->fresh()->isUsed());
    }

    public function test_a_post_with_blocks_shows_its_photo_on_the_public_page(): void
    {
        $photo = $this->photo('Zabieg', 'Zabieg w gabinecie');
        $this->makePost('<p>Tekst.</p>'.$this->block('photo', ['photo_id' => (string) $photo->id, 'caption' => 'Podpis']));

        $this->get('/blog/wpis-z-blokami')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('post.html', fn ($html) => str_contains($html, 'alt="Zabieg w gabinecie"') && str_contains($html, '<figcaption>Podpis</figcaption>'))->etc());
    }

    public function test_the_editor_offers_the_blocks_and_no_file_uploads(): void
    {
        Livewire::test(CreatePost::class)->assertFormFieldExists('body', function (RichEditor $editor): bool {
            $buttons = collect($editor->getToolbarButtons())->flatten()->all();

            return in_array('customBlocks', $buttons, true)
                && in_array('table', $buttons, true)
                && ! in_array('attachFiles', $buttons, true)
                && collect($editor->getCustomBlocks())->map(fn ($block) => $block::getId())->sort()->values()->all() === ['gallery', 'photo'];
        });
    }

    public function test_a_post_with_blocks_can_be_created_in_the_panel(): void
    {
        $photo = $this->photo();

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Wpis z panelu',
                'slug' => 'wpis-z-panelu',
                'excerpt' => 'Zajawka.',
                'body' => '<p>Wstęp.</p>'.$this->block('photo', ['photo_id' => (string) $photo->id, 'size' => 'wide']),
                'author_name' => 'Jan Nowak',
                'is_published' => true,
                'published_at' => now()->subHour()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::where('slug', 'wpis-z-panelu')->firstOrFail();

        $this->assertSame([$photo->id], $post->photos()->pluck('photos.id')->all());
        $this->get('/blog/wpis-z-panelu')->assertOk();
    }
}

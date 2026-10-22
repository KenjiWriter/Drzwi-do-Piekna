<?php

namespace Tests\Feature;

use App\Filament\Resources\Photos\Pages\ListPhotos;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Photo;
use App\Models\Post;
use App\Models\User;
use App\Support\PhotoStore;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class PostPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Photo::DISK);
        $this->seed();
        $this->actingAs(User::factory()->create());
    }

    private function form(array $overrides = []): array
    {
        return [
            'title' => 'Nowy wpis o pielęgnacji',
            'slug' => 'nowy-wpis-o-pielegnacji',
            'excerpt' => 'Krótka zajawka wpisu.',
            'body' => '<p>Wstęp.</p><h2>Śródtytuł</h2><p>Treść wpisu.</p>',
            'author_name' => 'Jan Nowak',
            'author_role' => 'Fizjoterapeuta',
            'is_published' => true,
            'published_at' => now()->subHour()->format('Y-m-d H:i:s'),
            ...$overrides,
        ];
    }

    public function test_the_blog_pages_of_the_panel_load(): void
    {
        $post = Post::firstOrFail();

        foreach (['/admin/posts', '/admin/posts/create', "/admin/posts/{$post->id}/edit"] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_the_list_shows_the_migrated_articles(): void
    {
        Livewire::test(ListPosts::class)->assertCanSeeTableRecords(Post::all())->assertCountTableRecords(4);
    }

    public function test_a_post_is_created_and_appears_on_the_blog(): void
    {
        Livewire::test(CreatePost::class)
            ->fillForm($this->form())
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(Post::where('slug', 'nowy-wpis-o-pielegnacji')->exists());

        $this->get('/blog/nowy-wpis-o-pielegnacji')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('post.author.role', 'Fizjoterapeuta')->etc());
        $this->get('/blog')->assertInertia(fn (Assert $page) => $page->has('posts', 5)->where('posts.0.title', 'Nowy wpis o pielęgnacji')->etc());
    }

    public function test_slug_must_be_unique_and_url_safe(): void
    {
        Livewire::test(CreatePost::class)
            ->fillForm($this->form(['slug' => 'filtry-przeciwsloneczne']))
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);

        Livewire::test(CreatePost::class)
            ->fillForm($this->form(['slug' => 'Zły Adres']))
            ->call('create')
            ->assertHasFormErrors(['slug' => 'regex']);
    }

    public function test_a_published_post_needs_a_publication_date(): void
    {
        Livewire::test(CreatePost::class)
            ->fillForm($this->form(['published_at' => null]))
            ->call('create')
            ->assertHasFormErrors(['published_at' => 'required']);

        // Szkic może nie mieć daty.
        Livewire::test(CreatePost::class)
            ->fillForm($this->form(['published_at' => null, 'is_published' => false]))
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_title_excerpt_body_and_author_are_required(): void
    {
        Livewire::test(CreatePost::class)
            ->fillForm($this->form(['title' => '', 'excerpt' => '', 'body' => '', 'author_name' => '']))
            ->call('create')
            ->assertHasFormErrors(['title' => 'required', 'excerpt' => 'required', 'author_name' => 'required', 'body']);
    }

    public function test_unpublishing_a_post_hides_it_from_the_site(): void
    {
        $post = Post::where('slug', 'filtry-przeciwsloneczne')->firstOrFail();
        $this->get('/blog/filtry-przeciwsloneczne')->assertOk();

        Livewire::test(EditPost::class, ['record' => $post->id])
            ->fillForm(['is_published' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/blog/filtry-przeciwsloneczne')->assertNotFound();
    }

    public function test_a_post_photo_shows_on_the_list_and_in_the_article(): void
    {
        $file = UploadedFile::fake()->image('x.jpg', 1200, 800);
        $photo = app(PhotoStore::class)->store($file->getRealPath(), 'Słońce', 'Plaża o zachodzie słońca');
        $post = Post::where('slug', 'jak-slonce-dziala-na-organizm')->firstOrFail();

        Livewire::test(EditPost::class, ['record' => $post->id])->fillForm(['photo_id' => $photo->id])->call('save')->assertHasNoFormErrors();

        $this->get('/blog')->assertInertia(fn (Assert $page) => $page->where('posts', fn ($posts) => collect($posts)->contains(fn ($p) => ($p['photo']['alt'] ?? null) === 'Plaża o zachodzie słońca'))->etc());

        $response = $this->get('/blog/jak-slonce-dziala-na-organizm')->assertOk();
        $response->assertSee('<meta property="og:image" content="'.url($photo->url(1440)).'"', false);
    }

    public function test_a_photo_used_by_a_post_cannot_be_deleted(): void
    {
        $file = UploadedFile::fake()->image('x.jpg', 1200, 800);
        $photo = app(PhotoStore::class)->store($file->getRealPath(), 'Słońce', 'Plaża');
        Post::first()->update(['photo_id' => $photo->id]);

        $this->assertSame(1, $photo->usageCount());

        Livewire::test(ListPhotos::class)->assertActionHidden(TestAction::make('delete')->table($photo));
    }
}

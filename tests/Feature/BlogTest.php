<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Support\PostContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    private const SLUGS = [
        'filtry-przeciwsloneczne',
        'pielegnacja-posloneczna',
        'jak-slonce-dziala-na-organizm',
        'bezpieczne-czy-nie-sprawdz-w-czyje-rece-sie-oddajesz',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function makePost(array $attributes = []): Post
    {
        return Post::create([
            'slug' => 'nowy-wpis',
            'title' => 'Nowy wpis',
            'excerpt' => 'Zajawka nowego wpisu.',
            'body' => '<p>Pierwszy akapit.</p><h2>Śródtytuł</h2><p>Drugi akapit.</p>',
            'author_name' => 'Jan Nowak',
            'author_role' => 'Fizjoterapeuta',
            'is_published' => true,
            'published_at' => now()->subDay(),
            ...$attributes,
        ]);
    }

    public function test_the_four_articles_from_the_old_site_are_listed_newest_first(): void
    {
        $this->get('/blog')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Index')
                ->has('posts', 4)
                ->where('posts', fn ($posts) => collect($posts)->pluck('url')->all() === array_map(fn ($s) => "/blog/{$s}", self::SLUGS))
                ->where('posts.0.published_label', '26 sierpnia 2020')
                ->etc());
    }

    public function test_the_articles_keep_their_original_authors_and_dates(): void
    {
        $this->assertSame('dr Anita Symonowicz', Post::where('slug', self::SLUGS[3])->value('author_name'));
        $this->assertSame('Katarzyna Klepacz', Post::where('slug', self::SLUGS[2])->value('author_name'));
        $this->assertSame('2018-08-26', Post::where('slug', self::SLUGS[3])->firstOrFail()->published_at->toDateString());
    }

    public function test_unpublished_and_scheduled_posts_stay_hidden(): void
    {
        $this->makePost(['slug' => 'szkic', 'is_published' => false]);
        $this->makePost(['slug' => 'za-tydzien', 'published_at' => now()->addWeek()]);

        $this->get('/blog')->assertInertia(fn (Assert $page) => $page->has('posts', 4));
        $this->get('/blog/szkic')->assertNotFound();
        $this->get('/blog/za-tydzien')->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('/blog/szkic', false)->assertDontSee('/blog/za-tydzien', false);

        $this->travel(8)->days();

        $this->get('/blog/za-tydzien')->assertOk();
    }

    public function test_a_post_page_carries_the_article_and_its_seo(): void
    {
        $post = Post::where('slug', 'jak-slonce-dziala-na-organizm')->firstOrFail();

        $response = $this->get('/blog/jak-slonce-dziala-na-organizm')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Show')
                ->where('post.title', 'Jak słońce działa na nasz organizm?')
                ->where('post.author.name', 'Katarzyna Klepacz')
                ->where('post.author.role', 'Kosmetolog, Drzwi do Piękna')
                ->where('post.html', fn ($html) => str_contains($html, '<h2>Pozytywne oddziaływanie promieni słonecznych</h2>'))
                ->etc());

        $response->assertSee('<link rel="canonical" href="'.url('/blog/jak-slonce-dziala-na-organizm').'"', false);
        $response->assertSee('<meta property="og:type" content="article"', false);
        $response->assertSee('"@type":"BlogPosting"', false);
        $response->assertSee('"headline":"Jak słońce działa na nasz organizm?"', false);
        $response->assertSee('"datePublished":"'.$post->published_at->toAtomString().'"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertSee('"name":"Katarzyna Klepacz"', false);
    }

    public function test_the_blog_is_in_the_menu_and_the_sitemap(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('nav', fn ($nav) => collect($nav)->contains('href', '/blog')));

        $sitemap = $this->get('/sitemap.xml')->assertOk();
        $sitemap->assertSee(url('/blog'), false);
        foreach (self::SLUGS as $slug) {
            $sitemap->assertSee(url("/blog/{$slug}"), false);
        }
    }

    public function test_post_content_is_sanitised_before_it_reaches_the_page(): void
    {
        $html = PostContent::toHtml(implode('', [
            '<h1>Nagłówek pierwszego poziomu</h1>',
            '<script>alert(1)</script><p>Tekst <img src="x" onerror="alert(1)"></p>',
            '<p><a href="javascript:alert(1)">kliknij</a> <a href="https://example.org/a">zewnętrzny</a> <a href="/zabiegi/twarz">własny</a></p>',
            '<iframe src="https://evil.example"></iframe>',
            '<table><tbody><tr><th><p>A</p></th><th><p>B</p></th></tr><tr><td><p>1</p></td><td><p>2</p></td></tr></tbody></table>',
            '<p style="text-align: center;">Na środku</p>',
        ]));

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        // Jedyny h1 na stronie to tytuł wpisu.
        $this->assertStringNotContainsString('<h1', $html);
        $this->assertStringContainsString('<h2>Nagłówek pierwszego poziomu</h2>', $html);
        $this->assertStringContainsString('<div class="ddp-prose__table"><table>', $html);
        $this->assertStringContainsString('href="https://example.org/a"', $html);
        $this->assertStringContainsString('<a href="/zabiegi/twarz">', $html);
        $this->assertStringContainsString('text-align: center', $html);
    }

    public function test_reading_time_is_at_least_one_minute(): void
    {
        $this->assertSame(1, $this->makePost(['body' => 'Krótko.'])->readingMinutes());
        $this->assertSame(5, Post::where('slug', 'jak-slonce-dziala-na-organizm')->firstOrFail()->readingMinutes());
    }

    public function test_migrated_articles_follow_the_content_rules(): void
    {
        foreach (Post::all() as $post) {
            // Zasada projektu: bez nazw leków na receptę i bez obietnic wyniku w treściach publicznych.
            foreach (['botulin', 'botoks', 'botox', 'gwarantuj', 'idealn'] as $forbidden) {
                $this->assertStringNotContainsStringIgnoringCase($forbidden, $post->body, "{$post->slug}: {$forbidden}");
            }
            $this->assertLessThanOrEqual(400, mb_strlen($post->excerpt));
        }
    }

    public function test_every_internal_link_in_the_articles_leads_to_a_page(): void
    {
        $links = collect();

        foreach (Post::all() as $post) {
            preg_match_all('~href="(/[^"#]*)"~', $post->body, $matches);
            $links = $links->merge($matches[1]);
        }

        $this->assertGreaterThanOrEqual(6, $links->unique()->count());

        foreach ($links->unique() as $path) {
            $this->get($path)->assertOk();
        }
    }
}

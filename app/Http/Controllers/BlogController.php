<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\Seo;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(): Response
    {
        $posts = Post::published()->with('photo')->get();

        return Inertia::render('Blog/Index', [
            'content' => trans('pages.blog'),
            'posts' => $posts->map(fn (Post $post) => $this->summary($post))->all(),
            'seo' => Seo::page(
                title: 'Blog o skórze i medycynie estetycznej | Drzwi do Piękna',
                description: 'Porady o pielęgnacji skóry, ochronie przed słońcem i bezpiecznych zabiegach medycyny estetycznej. Blog gabinetu Drzwi do Piękna w Łodzi.',
                path: '/blog',
                crumbs: [['Blog', '/blog']],
                type: 'Blog',
            ),
        ]);
    }

    public function show(string $slug): Response
    {
        $post = Post::published()->with('photo')->where('slug', $slug)->firstOrFail();

        $path = $post->url();
        $photoUrl = $post->photo ? url($post->photo->url(1440)) : null;

        return Inertia::render('Blog/Show', [
            'content' => trans('pages.blog'),
            'post' => [
                ...$this->summary($post),
                'html' => $post->html(),
                'author' => ['name' => $post->author_name, 'role' => $post->author_role],
            ],
            'seo' => Seo::page(
                title: $post->meta_title ?: Str::limit($post->title, 45, '').' | Blog | Drzwi do Piękna',
                description: $post->meta_description ?: Str::limit($post->excerpt, 155),
                path: $path,
                crumbs: [['Blog', '/blog'], [$post->title, $path]],
                extra: [$this->article($post, $photoUrl)],
                image: $photoUrl,
                ogType: 'article',
            ),
        ]);
    }

    /** @return array<string, mixed> */
    private function summary(Post $post): array
    {
        return [
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'url' => $post->url(),
            'photo' => $post->photo?->toImage(),
            'published_iso' => $post->published_at->toDateString(),
            'published_label' => $post->published_at->translatedFormat('j F Y'),
            'minutes' => $post->readingMinutes(),
        ];
    }

    /** @return array<string, mixed> węzeł JSON-LD BlogPosting */
    private function article(Post $post, ?string $photoUrl): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $post->excerpt,
            'url' => url($post->url()),
            'mainEntityOfPage' => ['@id' => url($post->url()).'#page'],
            'inLanguage' => 'pl-PL',
            'datePublished' => $post->published_at->toAtomString(),
            'dateModified' => max($post->updated_at, $post->published_at)->toAtomString(),
            'image' => $photoUrl,
            'author' => array_filter(['@type' => 'Person', 'name' => $post->author_name, 'jobTitle' => $post->author_role]),
            'publisher' => ['@id' => url('/').'#clinic'],
        ]);
    }
}

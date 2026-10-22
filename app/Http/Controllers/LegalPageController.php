<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class LegalPageController extends Controller
{
    public function __invoke(string $slug): Response
    {
        $page = Page::where('slug', $slug)->firstOrFail();
        $draft = $page->isDraft();

        return Inertia::render('Legal/Show', [
            'content' => trans('pages.legal'),
            'page' => [
                ...$page->content(),
                'title' => $page->title,
                'draft' => $draft,
                'effective_label' => $page->effective_from?->translatedFormat('j F Y'),
                'updated_label' => $page->updated_at->translatedFormat('j F Y'),
            ],
            'seo' => Seo::page(
                title: $page->title.' | Drzwi do Piękna',
                description: 'Jak gabinet Drzwi do Piękna w Łodzi przetwarza dane osobowe z formularza kontaktowego, jakich plików cookies używa i jakie prawa przysługują osobom, których dane dotyczą.',
                path: '/'.$page->slug,
                crumbs: [[$page->title, '/'.$page->slug]],
                // Wersja robocza nie powinna trafić do wyszukiwarek.
                noindex: $draft,
            ),
        ]);
    }
}

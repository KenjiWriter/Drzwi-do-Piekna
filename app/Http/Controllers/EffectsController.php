<?php

namespace App\Http\Controllers;

use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class EffectsController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Effects', [
            'content' => trans('pages.effects'),
            'seo' => Seo::page(
                title: 'Efekty zabiegów medycyny estetycznej | Drzwi do Piękna',
                description: 'Naturalne efekty zabiegów medycyny estetycznej w Łodzi. Zdjęcia przed i po, publikowane wyłącznie za zgodą pacjentek.',
                path: '/efekty',
                crumbs: [['Efekty', '/efekty']],
            ),
        ]);
    }
}

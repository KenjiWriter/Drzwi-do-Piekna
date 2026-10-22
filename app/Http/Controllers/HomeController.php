<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use App\Models\TreatmentCategory;
use App\Support\Images;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $content = trans('home');

        // Kafle zabiegów to pierwsze cztery opublikowane obszary z bazy.
        $content['treatments']['items'] = TreatmentCategory::published()->with('photo')->take(4)->get()
            ->map(fn (TreatmentCategory $c) => [
                'title' => $c->name,
                'image' => Images::resolve($c->photo, $c->image_key),
                'href' => '/zabiegi/'.$c->slug,
            ])
            ->values()
            ->all();

        $content['reviews'] = $this->reviews($content['reviews']);

        return Inertia::render('Home', [
            'seo' => Seo::home(),
            'content' => $content,
        ]);
    }

    /**
     * Opinie z bazy. Bez opublikowanych opinii sekcja znika ze strony. Uwaga o opinii przykładowej
     * pokazuje się tylko wtedy, gdy któraś z widocznych opinii jest demonstracyjna.
     *
     * @param  array{title: string, note: string}  $texts
     * @return array<string, mixed>|null
     */
    private function reviews(array $texts): ?array
    {
        $testimonials = Testimonial::published()->get();

        if ($testimonials->isEmpty()) {
            return null;
        }

        return [
            'title' => $texts['title'],
            'items' => $testimonials->map(fn (Testimonial $t) => [
                'quote' => $t->quote,
                'author' => $t->author,
                'source' => $t->source,
                'source_url' => $t->source_url,
            ])->all(),
            'note' => $testimonials->contains('is_demo', true) ? $texts['note'] : null,
        ];
    }
}

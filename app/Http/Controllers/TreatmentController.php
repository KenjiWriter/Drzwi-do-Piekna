<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use App\Models\TreatmentCategory;
use App\Support\Images;
use App\Support\Seo;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TreatmentController extends Controller
{
    public function index(): Response
    {
        $categories = TreatmentCategory::published()->with(['photo', 'treatments' => fn ($q) => $q->where('is_published', true)])->get();

        return Inertia::render('Treatments/Index', [
            'content' => trans('pages.treatments'),
            'categories' => $categories->map(fn (TreatmentCategory $c) => $this->categoryData($c, withTreatments: true))->all(),
            'seo' => Seo::page(
                title: 'Zabiegi medycyny estetycznej w Łodzi | Drzwi do Piękna',
                description: 'Zabiegi na twarz, usta, szyję i dekolt, jakość skóry oraz ciało. Naturalne efekty i indywidualne podejście. Gabinet medycyny estetycznej w Łodzi.',
                path: '/zabiegi',
                crumbs: [['Zabiegi', '/zabiegi']],
                type: 'CollectionPage',
            ),
        ]);
    }

    public function category(string $category): Response
    {
        $record = TreatmentCategory::published()->where('slug', $category)->firstOrFail();
        $record->load(['photo', 'treatments' => fn ($q) => $q->where('is_published', true)]);

        return Inertia::render('Treatments/Category', [
            'content' => trans('pages.treatments'),
            'category' => $this->categoryData($record, withTreatments: true),
            'seo' => Seo::page(
                title: $record->meta_title ?: $record->name.' | Zabiegi | Drzwi do Piękna',
                description: $record->meta_description ?: Str::limit($record->summary.' '.$record->intro, 155),
                path: '/zabiegi/'.$record->slug,
                crumbs: [['Zabiegi', '/zabiegi'], [$record->name, '/zabiegi/'.$record->slug]],
                type: 'CollectionPage',
            ),
        ]);
    }

    public function show(string $category, string $treatment): Response
    {
        $record = Treatment::published()
            ->where('slug', $treatment)
            ->whereHas('category', fn ($q) => $q->where('slug', $category)->where('is_published', true))
            ->with(['category', 'photo'])
            ->firstOrFail();

        $path = $record->url();
        $photoUrl = $record->photo ? url($record->photo->url(1440)) : null;

        return Inertia::render('Treatments/Show', [
            'content' => trans('pages.treatments'),
            'treatment' => [
                'name' => $record->name,
                'summary' => $record->summary,
                'paragraphs' => $record->description ? preg_split('/\n{2,}/', trim($record->description)) : [],
                'indications' => $record->indications ?? [],
                'effects' => $record->effects ?? [],
                'duration' => $record->duration,
                'photo' => $record->photo?->toImage(),
                'category' => ['name' => $record->category->name, 'url' => '/zabiegi/'.$record->category->slug],
            ],
            'seo' => Seo::page(
                title: $record->meta_title ?: Str::limit($record->name, 45, '').' | Drzwi do Piękna',
                description: $record->meta_description ?: Str::limit($record->summary, 155),
                path: $path,
                crumbs: [
                    ['Zabiegi', '/zabiegi'],
                    [$record->category->name, '/zabiegi/'.$record->category->slug],
                    [$record->name, $path],
                ],
                extra: [array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'Service',
                    'name' => $record->name,
                    'description' => $record->summary,
                    'url' => url($path),
                    'image' => $photoUrl,
                    'areaServed' => 'Łódź',
                    'provider' => ['@id' => url('/').'#clinic'],
                ])],
                image: $photoUrl,
            ),
        ]);
    }

    /** @return array<string, mixed> */
    private function categoryData(TreatmentCategory $category, bool $withTreatments = false): array
    {
        $data = [
            'slug' => $category->slug,
            'name' => $category->name,
            'summary' => $category->summary,
            'intro' => $category->intro,
            'image' => Images::resolve($category->photo, $category->image_key),
            'url' => '/zabiegi/'.$category->slug,
        ];

        if ($withTreatments) {
            $data['treatments'] = $category->treatments->map(fn (Treatment $t) => [
                'name' => $t->name,
                'summary' => $t->summary,
                'url' => '/zabiegi/'.$category->slug.'/'.$t->slug,
            ])->all();
        }

        return $data;
    }
}

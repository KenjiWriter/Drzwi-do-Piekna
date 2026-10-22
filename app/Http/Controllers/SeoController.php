<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use Illuminate\Http\Response;

/**
 * robots.txt i sitemap.xml generowane dynamicznie, żeby środowiska inne niż
 * produkcyjne (staging, lokalne) nigdy nie trafiły do indeksu wyszukiwarki.
 */
class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = app()->environment('production')
            ? ['User-agent: *', 'Allow: /', 'Disallow: /admin', '', 'Sitemap: '.url('/sitemap.xml')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        // [ścieżka, data ostatniej zmiany lub null]
        $entries = collect([['/', null], ['/o-mnie', null], ['/zabiegi', null], ['/efekty', null], ['/cennik', null], ['/blog', null], ['/kontakt', null]]);

        foreach (TreatmentCategory::published()->get() as $category) {
            $entries->push(['/zabiegi/'.$category->slug, $category->updated_at]);
        }

        foreach (Treatment::published()->with('category')->get() as $treatment) {
            if ($treatment->category->is_published) {
                $entries->push([$treatment->url(), $treatment->updated_at]);
            }
        }

        foreach (Post::published()->get() as $post) {
            $entries->push([$post->url(), $post->updated_at]);
        }

        // Dokument w wersji roboczej nie trafia do mapy strony (i ma noindex).
        $privacy = Page::where('slug', Page::PRIVACY)->first();

        if ($privacy && ! $privacy->isDraft()) {
            $entries->push(['/'.$privacy->slug, $privacy->updated_at]);
        }

        $urls = $entries->map(function (array $entry) {
            [$path, $updated] = $entry;
            $lastmod = $updated ? '<lastmod>'.$updated->toAtomString().'</lastmod>' : '';

            return '  <url><loc>'.e(url($path)).'</loc>'.$lastmod.'</url>';
        })->implode("\n");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .$urls."\n"
            .'</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}

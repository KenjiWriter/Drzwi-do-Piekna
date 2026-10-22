<?php

namespace App\Http\Controllers;

use App\Models\PriceGroup;
use App\Models\PriceItem;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class PriceListController extends Controller
{
    public function __invoke(): Response
    {
        $groups = PriceGroup::query()->with('items')->orderBy('sort_order')->get();

        return Inertia::render('Prices', [
            'content' => trans('pages.prices'),
            'groups' => $groups->map(fn (PriceGroup $group) => [
                'slug' => $group->slug,
                'name' => $group->name,
                'note' => $group->note,
                // Pozycje pogrupowane w podgrupy w kolejności z bazy (pozycje bez podgrupy trafiają do klucza "").
                'sections' => $group->items->groupBy(fn (PriceItem $i) => $i->subgroup ?? '')->map(fn ($items, $name) => [
                    'name' => $name === '' ? null : $name,
                    'items' => $items->map(fn (PriceItem $i) => [
                        'name' => $i->name,
                        'min' => $i->price_min,
                        'max' => $i->price_max,
                        'from' => $i->is_from,
                        'pack' => $i->pack_price,
                        'packLabel' => $i->pack_label,
                        'note' => $i->note,
                    ])->values()->all(),
                ])->values()->all(),
            ])->all(),
            'seo' => Seo::page(
                title: 'Cennik zabiegów medycyny estetycznej, Łódź | Drzwi do Piękna',
                description: 'Cennik zabiegów gabinetu medycyny estetycznej Drzwi do Piękna w Łodzi: laseroterapia, wypełniacze, mezoterapia, zabiegi na ciało i pielęgnacja skóry.',
                path: '/cennik',
                crumbs: [['Cennik', '/cennik']],
            ),
        ]);
    }
}

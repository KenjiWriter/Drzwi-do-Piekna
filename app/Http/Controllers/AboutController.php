<?php

namespace App\Http\Controllers;

use App\Models\TeamMember;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('About', [
            'content' => trans('pages.about'),
            'team' => TeamMember::published()->with('photo')->get()
                ->map(fn (TeamMember $member) => [
                    'slug' => $member->slug,
                    'name' => $member->name,
                    'role' => $member->role,
                    'bio' => $member->bio,
                    'photo' => $member->photo?->toImage(),
                ])
                ->all(),
            'seo' => Seo::page(
                title: 'O mnie, lek. Anita Symonowicz | Drzwi do Piękna',
                description: 'Lekarz medycyny estetyczna i stomatolog z Łodzi. Wiedza, doświadczenie i uważność na pacjentkę. Poznaj lek. Anitę Symonowicz i zespół gabinetu.',
                path: '/o-mnie',
                crumbs: [['O mnie', '/o-mnie']],
                type: 'AboutPage',
            ),
        ]);
    }
}

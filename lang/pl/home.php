<?php

/*
|--------------------------------------------------------------------------
| Treści strony głównej
|--------------------------------------------------------------------------
|
| Teksty pochodzą z wizualizacji klienta i z obecnej strony. Opinia i zdjęcia
| przed/po są przykładami układu i trzeba je zastąpić materiałami klientki.
|
*/

return [
    'hero' => [
        'eyebrow' => ['Nowoczesna', 'medycyna estetyczna'],
        'title' => ['Piękno', 'w harmonii', 'z Tobą'],
        'lead' => 'Naturalne efekty. Świadome decyzje. Indywidualne podejście.',
        'cta' => 'Umów konsultację',
        'scroll' => 'Przewiń do kolejnej sekcji',
    ],

    'trust' => [
        'items' => [
            ['icon' => 'lotus', 'title' => 'Naturalne efekty', 'text' => 'Podkreślamy to, co w Tobie najpiękniejsze.'],
            ['icon' => 'user', 'title' => 'Indywidualne podejście', 'text' => 'Każdy plan terapii dopasowujemy do Twoich potrzeb.'],
            ['icon' => 'gem', 'title' => 'Bezpieczeństwo', 'text' => 'Pracujemy sprawdzonymi metodami i certyfikowanymi preparatami.'],
        ],
    ],

    'about' => [
        'eyebrow' => 'O mnie',
        'title' => 'Doświadczenie, któremu możesz zaufać',
        'text' => 'Łączę wiedzę medyczną z wyczuciem estetyki, aby osiągać naturalne i harmonijne efekty. W mojej pracy najważniejsze są Twoje potrzeby, bezpieczeństwo i długofalowe rezultaty.',
        'cta' => 'Poznaj mnie',
        'href' => '/o-mnie',
    ],

    'treatments' => [
        'eyebrow' => 'Zabiegi',
        'title' => 'Nowoczesne rozwiązania dla naturalnego piękna',
        'link' => ['label' => 'Zobacz wszystkie', 'href' => '/zabiegi'],
        // 'items' pochodzą z bazy (HomeController): pierwsze cztery opublikowane obszary.
    ],

    'journey' => [
        'title' => ['Twoje piękno.', 'Nasza wspólna podróż.'],
        'text' => 'Wierzę, że medycyna estetyczna to nie zmiana, ale podkreślenie Twojej naturalnej urody. Z troską, wiedzą i szacunkiem do indywidualności.',
        'cta' => 'Umów wizytę',
    ],

    'effects' => [
        'eyebrow' => 'Efekty',
        'title' => 'Efekty, które mówią same za siebie',
        'link' => ['label' => 'Zobacz więcej', 'href' => '/efekty'],
        'items' => [
            ['image' => 'ba-1', 'alt' => 'Skóra szyi'],
            ['image' => 'ba-2', 'alt' => 'Skóra okolic ust'],
            ['image' => 'ba-3', 'alt' => 'Skóra twarzy'],
        ],
        'note' => 'Zdjęcia poglądowe (demo). W wersji docelowej: efekty prawdziwych pacjentek, wyłącznie za pisemną zgodą.',
    ],

    'philosophy' => [
        'title' => 'Piękno to harmonia, nie zmiana.',
        'link' => ['label' => 'Poznaj filozofię', 'href' => '/o-mnie#filozofia'],
    ],

    'reviews' => [
        'title' => 'Co mówią moje pacjentki',
        // Opinie są w bazie i edytuje się je w panelu (Pacjentki, Opinie). Tu tylko teksty sekcji.
        'note' => 'Przykładowa opinia z wizualizacji. Docelowo: prawdziwe opinie z ZnanyLekarz lub Google, za zgodą pacjentek.',
    ],

    'contact' => [
        'eyebrow' => 'Kontakt',
        'title' => 'Umów konsultację',
        'text' => 'Porozmawiajmy o Twoich potrzebach i możliwościach. Dobierzemy najlepsze rozwiązanie dla Ciebie.',
        'cta' => 'Umów wizytę',
        'write' => 'Napisz do nas',
    ],
];

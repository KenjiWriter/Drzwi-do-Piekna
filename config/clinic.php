<?php

/*
|--------------------------------------------------------------------------
| Dane gabinetu
|--------------------------------------------------------------------------
|
| Jedno źródło prawdy dla nagłówka, stopki, danych strukturalnych (JSON-LD)
| i przycisków kontaktu. Zmiana tutaj zmienia je wszędzie.
|
*/

return [
    'name' => 'Drzwi do Piękna',
    'tagline' => 'Medycyna estetyczna',
    'doctor' => 'dr Anita Symonowicz',
    // Liczba płyt tła animacji powitalnej (public/intro/plate-0.webp ... , scripts/intro-plates.mjs). Skrypt w <head> losuje numer płyty.
    'intro_plates' => 4,
    'doctor_role' => 'Lekarz medycyny estetycznej, stomatolog',

    'phone' => '797 745 224',
    'phone_e164' => '+48797745224',
    'email' => 'gabinet@drzwidopiekna.pl',
    // Dokąd trafia powiadomienie o zgłoszeniu z formularza. Domyślnie na adres gabinetu; na stagingu ustaw własny
    // (CLINIC_NOTIFY_EMAIL), żeby testy formularza nie wysyłały wiadomości do prawdziwej skrzynki. Nie jest publiczne (PUBLIC_CLINIC_KEYS).
    'notify_email' => env('CLINIC_NOTIFY_EMAIL'),

    'address' => [
        'street' => 'Karolewska 29/31',
        'postal_code' => '90-560',
        'city' => 'Łódź',
        'country' => 'PL',
    ],

    // Rezerwacja działa w Moment.pl. Własny formularz pojawi się w fazie 2.
    'booking_url' => env('CLINIC_BOOKING_URL', 'https://www.moment.pl/drzwi-do-piekna'),
    'maps_url' => 'https://g.page/drzwidopiekna?share',

    'social' => [
        'instagram' => 'https://www.instagram.com/drzwidopiekna/',
        'facebook' => 'https://www.facebook.com/drzwidopiekna/',
        'znanylekarz' => 'https://www.znanylekarz.pl/anita-symonowicz/stomatolog-lekarz-medycyny-estetycznej/lodz',
    ],

    // Dokumenty prawne (treść w panelu: Treści, Dokumenty). Adresy stałe, bo linkuje je formularz i stopka.
    'legal' => [
        'privacy' => '/polityka-prywatnosci',
        'cookies' => '/polityka-prywatnosci#cookies',
    ],

    'nav' => [
        ['label' => 'O mnie', 'href' => '/o-mnie'],
        ['label' => 'Zabiegi', 'href' => '/zabiegi'],
        ['label' => 'Efekty', 'href' => '/efekty'],
        ['label' => 'Cennik', 'href' => '/cennik'],
        ['label' => 'Blog', 'href' => '/blog'],
        ['label' => 'Kontakt', 'href' => '/kontakt'],
    ],

    // Konto deweloperskie panelu /admin (tylko poza produkcją, patrz AdminUserSeeder) i wymóg 2FA.
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
        // Dwuskładnikowe logowanie jest wymagane w produkcji. ADMIN_2FA_REQUIRED=true wymusza je też w innym środowisku
        // (np. na stagingu), a false wyłącza (deploy.sh nie pozwoli na false w produkcji).
        'require_2fa' => filter_var(env('ADMIN_2FA_REQUIRED', env('APP_ENV') === 'production'), FILTER_VALIDATE_BOOLEAN),
    ],

    // Stare adresy zabiegów bez własnej strony: slug ze starej strony => nowy adres (301).
    'legacy_fallbacks' => [
        'peptiglow-peelboost' => '/zabiegi/jakosc-skory',
        'lifting-twarzy-nicmi' => '/zabiegi/twarz',
        'lipoliza-iniekcyjna' => '/zabiegi/cialo',
        'masaze' => '/zabiegi/cialo',
        'nucleofill' => '/zabiegi/jakosc-skory',
        'retibooster' => '/zabiegi/szyja-i-dekolt/retibooster-therapy',
    ],

    // Angielskie adresy zabiegów ze starej strony (/en/zabieg/...) => polski slug starej strony.
    'legacy_aliases' => [
        'correction-of-mimic-wrinkles-with-the-botulinum-toxin' => 'korekta-zmarszczek-mimicznych-toksyna-botulinowa-botox',
        'wrinkles-correction-and-face-volumetry-by-hyaluronic-acid' => 'korekta-zmarszczek-i-wolumetria-twarzy',
        'face-lifting-and-skin-revitalization-with-resorbable-threads' => 'lifting-twarzy-nicmi',
        'injection-lipolysis' => 'lipoliza-iniekcyjna',
        'injection-mesotherapy' => 'mezoterapia-iniekcyjna',
    ],

    // Pozostałe stare adresy (bez ukośnika końcowego; ten normalizuje middleware) => nowy adres (301).
    'legacy_redirects' => [
        '/oferta' => '/zabiegi',
        '/oferta/meska-strefa' => '/zabiegi',
        '/zabieg' => '/zabiegi',
        '/o-firmie' => '/o-mnie',
        '/o-firmie/nasz-zespol' => '/o-mnie#zespol',
        '/o-firmie/nasz-zespol/{person}' => '/o-mnie#zespol',
        '/o-firmie/aktualnosci' => '/',
        '/filozofia' => '/o-mnie#filozofia',
        '/faq' => '/kontakt',

        // Wersja angielska, której nowa strona nie ma: kierujemy na polskie odpowiedniki.
        '/en' => '/',
        '/en/home-2' => '/',
        '/en/about-me' => '/o-mnie',
        '/en/about-us' => '/o-mnie',
        '/en/about-us/our-team' => '/o-mnie#zespol',
        '/en/offer' => '/zabiegi',
        '/en/zabieg' => '/zabiegi',
        '/en/pricing' => '/cennik',
        '/en/contact' => '/kontakt',
        '/en/blog-en' => '/blog',

        // Mapy strony WordPressa, które wyszukiwarki mają w pamięci.
        '/sitemap_index.xml' => '/sitemap.xml',
        '/wp-sitemap.xml' => '/sitemap.xml',
        '/page-sitemap.xml' => '/sitemap.xml',
        '/zabiegi-sitemap.xml' => '/sitemap.xml',
        '/post-sitemap.xml' => '/sitemap.xml',
    ],
];

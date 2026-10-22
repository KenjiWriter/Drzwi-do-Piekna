<?php

namespace App\Support;

/**
 * Buduje dane SEO strony: title, description, canonical, Open Graph, JSON-LD i preload obrazu LCP.
 * Szablon `app.blade.php` wypisuje je po stronie serwera, więc są w HTML
 * także wtedy, gdy renderowanie SSR jest niedostępne.
 */
class Seo
{
    public static function home(): array
    {
        return self::make(
            title: 'Drzwi do Piękna | Medycyna estetyczna Łódź',
            description: 'Gabinet medycyny estetycznej w Łodzi. Naturalne efekty, świadome decyzje i indywidualne podejście. Umów konsultację z lek. Anitą Symonowicz.',
            path: '/',
            jsonLd: [self::website(), self::business()],
            preload: self::heroPreload(),
        );
    }

    /**
     * Podstrona: title, description, okruszki (BreadcrumbList) i węzeł typu strony w JSON-LD.
     *
     * @param  array<int, array{0: string, 1: string}>  $crumbs  [[nazwa, ścieżka], ...] bez strony głównej
     * @param  array<int, array<string, mixed>>  $extra  dodatkowe węzły JSON-LD (np. Service)
     * @param  string|null  $image  bezwzględny adres zdjęcia do podglądu linku (Open Graph)
     * @param  string  $ogType  typ obiektu Open Graph: website albo article
     * @param  bool  $noindex  true wyłącza stronę z indeksu wyszukiwarek (np. dokument w wersji roboczej)
     * @return array<string, mixed>
     */
    public static function page(string $title, string $description, string $path, array $crumbs = [], string $type = 'WebPage', array $extra = [], ?string $image = null, string $ogType = 'website', bool $noindex = false): array
    {
        $jsonLd = [
            [
                '@context' => 'https://schema.org',
                '@type' => $type,
                '@id' => url($path).'#page',
                'name' => $title,
                'url' => url($path),
                'inLanguage' => 'pl-PL',
                'isPartOf' => ['@id' => url('/').'#website'],
            ],
            ...$extra,
        ];

        if ($crumbs) {
            $jsonLd[] = self::breadcrumbs($crumbs);
        }

        return self::make($title, $description, $path, $jsonLd, $image, noindex: $noindex, ogType: $ogType);
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $crumbs
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $crumbs): array
    {
        $items = [['Strona główna', '/'], ...$crumbs];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $crumb, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb[0],
                'item' => url($crumb[1]),
            ])->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $jsonLd
     * @param  array<int, array<string, string>>  $preload
     * @return array<string, mixed>
     */
    public static function make(
        string $title,
        string $description,
        string $path = '/',
        array $jsonLd = [],
        ?string $image = null,
        bool $noindex = false,
        array $preload = [],
        string $ogType = 'website',
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'canonical' => url($path),
            'image' => $image,
            'ogType' => $ogType,
            'siteName' => config('clinic.name'),
            'locale' => 'pl_PL',
            'noindex' => $noindex,
            'jsonLd' => $jsonLd,
            'preload' => $preload,
        ];
    }

    /**
     * Preload zdjęcia z pierwszego ekranu (LCP): szerokie dla desktopu, pionowe dla telefonu.
     *
     * @return array<int, array<string, string>>
     */
    public static function heroPreload(): array
    {
        $manifest = self::imageManifest();
        $preload = [];

        foreach (['hero' => ['(min-width: 1024px)', '58vw'], 'hero-m' => ['(max-width: 1023px)', '100vw']] as $key => [$media, $sizes]) {
            if (isset($manifest[$key])) {
                $preload[] = [
                    'href' => $manifest[$key]['src'],
                    'srcset' => $manifest[$key]['srcset'],
                    'sizes' => $sizes,
                    'media' => $media,
                ];
            }
        }

        return $preload;
    }

    /** @return array<string, array<string, mixed>> */
    private static function imageManifest(): array
    {
        $file = resource_path('js/images.json');

        return is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
    }

    /** @return array<string, mixed> */
    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'name' => config('clinic.name'),
            'url' => url('/'),
            'inLanguage' => 'pl-PL',
        ];
    }

    /** @return array<string, mixed> */
    public static function business(): array
    {
        $clinic = config('clinic');
        $address = $clinic['address'];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'MedicalBusiness',
            '@id' => url('/').'#clinic',
            'name' => $clinic['name'],
            'url' => url('/'),
            'telephone' => $clinic['phone_e164'],
            'email' => $clinic['email'],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $address['street'],
                'postalCode' => $address['postal_code'],
                'addressLocality' => $address['city'],
                'addressCountry' => $address['country'],
            ],
            'hasMap' => $clinic['maps_url'],
            'sameAs' => array_values($clinic['social']),
            'employee' => [
                '@type' => 'Physician',
                'name' => 'Anita Symonowicz',
                'honorificPrefix' => 'dr',
                'jobTitle' => $clinic['doctor_role'],
            ],
        ];
    }
}

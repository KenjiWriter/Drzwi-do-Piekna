<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;

/**
 * Stare adresy zabiegów z WordPressa (/zabieg/{slug}/) przekierowane na nowe (301),
 * żeby nie stracić pozycji w wyszukiwarce.
 */
class LegacyRedirectController extends Controller
{
    public function __invoke(string $legacy): RedirectResponse
    {
        // Angielskie adresy (/en/zabieg/...) sprowadzamy do polskiego sluga starej strony.
        $legacy = config('clinic.legacy_aliases')[$legacy] ?? $legacy;

        $treatment = Treatment::published()->where('legacy_slug', $legacy)->with('category')->first();

        if ($treatment) {
            return redirect($treatment->url(), 301);
        }

        // Zabiegi, które nie mają już własnej strony, kierujemy do najbliższej listy.
        $fallback = config('clinic.legacy_fallbacks')[$legacy] ?? null;

        abort_unless($fallback, 404);

        return redirect($fallback, 301);
    }
}

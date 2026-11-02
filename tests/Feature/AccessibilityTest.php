<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * WCAG 2.1: kontrast kolorów z tokenów (zwykła i kontrastowa wersja) oraz ustawienia dostępności odwiedzającej
 * (wielkość tekstu, kontrast), które skrypt w <head> odtwarza przed pierwszym malowaniem.
 */
class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    /** Pary tekst/tło używane w komponentach i wymagany kontrast (4,5:1 dla zwykłego tekstu, poziom AA). */
    private const PAIRS = [
        ['ink', 'surface'],
        ['ink', 'surface-alt'],
        ['ink-soft', 'surface'],
        ['ink-soft', 'surface-alt'],
        ['muted', 'surface'],
        ['muted', 'surface-alt'],
        ['heading', 'surface'],
        ['brand', 'surface'],
        ['on-brand', 'brand'],
        ['on-brand-soft', 'brand'],
        ['error', 'surface'],
        ['success', 'surface'],
    ];

    public function test_default_colours_meet_wcag_aa(): void
    {
        $tokens = $this->tokens(':root, [data-theme="light"]');

        foreach (self::PAIRS as [$text, $background]) {
            $ratio = $this->contrast($tokens[$text], $tokens[$background]);
            $this->assertGreaterThanOrEqual(4.5, $ratio, "--{$text} na --{$background}: {$ratio}:1");
        }
    }

    public function test_the_high_contrast_version_redefines_every_colour_and_reaches_aaa(): void
    {
        $default = $this->tokens(':root, [data-theme="light"]');
        $contrast = $this->tokens('html[data-contrast]');

        // Nowy kolor w tokenach musi dostać odpowiednik w wersji kontrastowej, inaczej zostanie w niej beżowo-bordowy.
        foreach (array_keys($default) as $name) {
            $this->assertArrayHasKey($name, $contrast, "Wersja kontrastowa nie ustawia --{$name}.");
        }

        foreach (self::PAIRS as [$text, $background]) {
            $ratio = $this->contrast($contrast[$text], $contrast[$background]);
            $this->assertGreaterThanOrEqual(7, $ratio, "Kontrast: --{$text} na --{$background}: {$ratio}:1");
        }
    }

    public function test_the_layout_restores_the_visitors_settings_before_the_first_paint(): void
    {
        $this->seed();
        $html = $this->get('/')->assertOk()->getContent();

        // Skrypt z ustawieniami jest w <head> przed stylami, więc strona nie mruga przy wejściu.
        $head = substr($html, 0, strpos($html, '</head>'));
        $this->assertStringContainsString("localStorage.getItem('ddp-a11y')", $head);
        $this->assertLessThan(strpos($head, '<link rel="stylesheet"') ?: strlen($head), strpos($head, 'ddp-a11y'));

        // W wersji kontrastowej nie gra animacja powitalna (beżowa scena z cienkimi kreskami).
        $this->assertStringContainsString("d.hasAttribute('data-contrast')", $head);
    }

    public function test_text_sizes_scale_the_root_font_size(): void
    {
        $css = file_get_contents(resource_path('css/site.css'));

        $this->assertStringContainsString('html[data-text-size="lg"] { --text-scale: 1.25; font-size: 125%; }', $css);
        $this->assertStringContainsString('html[data-text-size="xl"] { --text-scale: 1.5; font-size: 150%; }', $css);

        // Powiększanie działa tylko dlatego, że komponenty podają rozmiary pisma w rem/em, a nie w px.
        $components = file_get_contents(resource_path('css/ddp/components.css'));
        $this->assertDoesNotMatchRegularExpression('/font(?:-size)?:[^;]*\b\d+px/', $components);
    }

    /** @return array<string, string> nazwa tokenu bez `--` => kolor szesnastkowy, z rozwiązanymi var() */
    private function tokens(string $selector): array
    {
        $css = file_get_contents(resource_path('css/ddp/tokens.css'));
        $start = strpos($css, $selector.' {');
        $this->assertNotFalse($start, "Brak bloku {$selector} w tokens.css");
        $body = substr($css, $start, strpos($css, '}', $start) - $start);

        preg_match_all('/--([a-z0-9-]+):\s*([^;]+);/', $body, $matches, PREG_SET_ORDER);
        $raw = [];
        foreach ($matches as [, $name, $value]) {
            $raw[$name] = trim($value);
        }

        $resolve = function (string $value) use (&$resolve, $raw): string {
            return preg_match('/^var\(--([a-z0-9-]+)\)$/', $value, $m) ? $resolve($raw[$m[1]]) : $value;
        };

        return collect($raw)
            ->map($resolve)
            ->filter(fn (string $value) => preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/i', $value))
            ->all();
    }

    private function contrast(string $a, string $b): float
    {
        [$lighter, $darker] = [max($this->luminance($a), $this->luminance($b)), min($this->luminance($a), $this->luminance($b))];

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    private function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        [$r, $g, $b] = array_map(function (string $channel) {
            $c = hexdec($channel) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split($hex, 2));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Treść dokumentu prawnego z edytora wizualnego na HTML strony wraz ze spisem treści. Nagłówki H2 dostają
 * adresy (kotwice), żeby dało się do nich linkować, a sekcja o plikach cookies ma stały adres #cookies,
 * który linkuje stopka.
 */
class LegalContent
{
    /** @return array{html: string, toc: array<int, array{id: string, title: string}>} */
    public static function render(?string $body): array
    {
        $toc = [];
        $used = [];

        $html = preg_replace_callback('~<h2([^>]*)>(.*?)</h2>~su', function (array $match) use (&$toc, &$used): string {
            $title = trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $id = Str::slug($title) ?: 'sekcja';

            if (! isset($used['cookies']) && str_contains($id, 'cookies')) {
                $id = 'cookies';
            }

            for ($n = 2, $base = $id; isset($used[$id]); $n++) {
                $id = "{$base}-{$n}";
            }

            $used[$id] = true;
            $toc[] = ['id' => $id, 'title' => $title];

            return '<h2 id="'.$id.'"'.$match[1].'>'.$match[2].'</h2>';
        }, PostContent::toHtml($body));

        return ['html' => $html, 'toc' => $toc];
    }
}

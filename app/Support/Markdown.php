<?php

namespace App\Support;

use Illuminate\Support\Str;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;

/**
 * Markdown na HTML, którym zasilamy edytor wizualny wpisów (seeder artykułów ze starej strony i migracja
 * wpisów zapisanych wcześniej jako Markdown). Surowy HTML z treści jest usuwany, a linki `javascript:`
 * i `data:` wyłączone. Treść wpisów w panelu edytuje się już w edytorze wizualnym, nie w Markdown.
 */
class Markdown
{
    public static function toHtml(string $markdown): string
    {
        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'external_link' => [
                'internal_hosts' => array_filter([parse_url((string) config('app.url'), PHP_URL_HOST), 'drzwidopiekna.pl', 'www.drzwidopiekna.pl']),
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ], [new ExternalLinkExtension]);

        // Tytuł wpisu jest jedynym h1 na stronie, więc nagłówek pierwszego poziomu z treści schodzi na h2.
        return preg_replace('~<(/?)h1(?=[\s>])~', '<$1h2', $html);
    }
}

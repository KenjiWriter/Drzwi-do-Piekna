<?php

namespace App\Support;

use App\Filament\RichContent\GalleryBlock;
use App\Filament\RichContent\PhotoBlock;
use Filament\Forms\Components\RichEditor\RichContentRenderer;

/**
 * Treść wpisu z edytora wizualnego (HTML z blokami) na HTML strony. Jedno miejsce dla panelu i strony,
 * żeby edytor i wynik używały tych samych bloków.
 */
class PostContent
{
    /** @return array<int, class-string<\Filament\Forms\Components\RichEditor\RichContentCustomBlock>> */
    public static function blocks(): array
    {
        return [PhotoBlock::class, GalleryBlock::class];
    }

    /**
     * Bezpieczny HTML (renderer Filament przepuszcza wynik przez sanityzator: znikają skrypty, atrybuty
     * zdarzeń, ramki i niebezpieczne linki).
     */
    public static function toHtml(?string $body): string
    {
        if (blank($body)) {
            return '';
        }

        $html = RichContentRenderer::make($body)->customBlocks(self::blocks())->toHtml();

        // Tytuł wpisu jest jedynym h1 na stronie, więc nagłówek pierwszego poziomu z treści schodzi na h2.
        $html = preg_replace('~<(/?)h1(?=[\s>])~', '<$1h2', $html);

        // Tabela w osobnym kontenerze, żeby na telefonie przewijała się w bok zamiast rozpychać stronę.
        return str_replace(['<table', '</table>'], ['<div class="ddp-prose__table"><table', '</table></div>'], $html);
    }

    /**
     * Identyfikatory zdjęć z biblioteki użytych w blokach treści. Dzięki nim panel wie, które zdjęcia są
     * w użyciu (i nie pozwala ich usunąć).
     *
     * @return array<int, int>
     */
    public static function photoIds(?string $body): array
    {
        if (blank($body)) {
            return [];
        }

        $ids = [];
        $walk = function (array $node) use (&$walk, &$ids): void {
            if (($node['type'] ?? null) === 'customBlock') {
                $config = $node['attrs']['config'] ?? [];
                $ids[] = $config['photo_id'] ?? null;

                foreach ($config['items'] ?? [] as $item) {
                    $ids[] = $item['photo_id'] ?? null;
                }
            }

            foreach ($node['content'] ?? [] as $child) {
                $walk($child);
            }
        };

        $walk(RichContentRenderer::make($body)->customBlocks(self::blocks())->toArray());

        return array_values(array_unique(array_map('intval', array_filter($ids, fn ($id) => filled($id)))));
    }
}

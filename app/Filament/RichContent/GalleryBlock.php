<?php

namespace App\Filament\RichContent;

use App\Filament\Forms\PhotoSelect;
use App\Models\Photo;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/** Blok „Zdjęcia obok siebie": dwa lub trzy zdjęcia z biblioteki w jednym rzędzie (na telefonie jedno pod drugim przy trzech). */
class GalleryBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'gallery';
    }

    public static function getLabel(): string
    {
        return 'Zdjęcia obok siebie';
    }

    public static function getIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedSquares2x2;
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Zdjęcia obok siebie')
            ->modalDescription('Dodaj dwa lub trzy zdjęcia z biblioteki. Zdjęcia zostaną przycięte do tych samych proporcji, żeby tworzyły równy rząd.')
            ->modalSubmitActionLabel('Wstaw zdjęcia')
            ->schema([
                Repeater::make('items')
                    ->label('Zdjęcia')
                    ->schema([
                        PhotoSelect::standalone()->required(),
                        TextInput::make('caption')->label('Podpis')->maxLength(120),
                    ])
                    ->minItems(2)
                    ->maxItems(3)
                    ->defaultItems(2)
                    ->addActionLabel('Dodaj zdjęcie')
                    ->reorderable()
                    ->required(),
            ]);
    }

    /** @param  array<string, mixed>  $config */
    public static function getPreviewLabel(array $config): string
    {
        return 'Zdjęcia obok siebie ('.count(self::photos($config)).')';
    }

    /** @param  array<string, mixed>  $config */
    public static function toPreviewHtml(array $config): ?string
    {
        $items = self::photos($config);

        if (! $items) {
            return null;
        }

        $html = array_map(fn (array $item) => '<img src="'.e($item['photo']->url(480)).'" alt="'.e($item['photo']->alt).'" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:0.25rem">', $items);

        return '<div style="display:grid;grid-template-columns:repeat('.count($items).',minmax(0,1fr));gap:0.5rem">'.implode('', $html).'</div>';
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): ?string
    {
        $items = self::photos($config);

        if (! $items) {
            return '';
        }

        $figures = array_map(fn (array $item) => '<figure>'.PhotoBlock::image($item['photo'], '(min-width: 1024px) 240px, 50vw')
            .($item['caption'] ? '<figcaption>'.e($item['caption']).'</figcaption>' : '').'</figure>', $items);

        return '<div class="ddp-gallery ddp-gallery--'.count($items).'">'.implode('', $figures).'</div>';
    }

    /**
     * Zdjęcia usunięte z biblioteki wypadają z rzędu zamiast zostawiać pustą ramkę.
     *
     * @param  array<string, mixed>  $config
     * @return array<int, array{photo: Photo, caption: ?string}>
     */
    private static function photos(array $config): array
    {
        $items = array_values(array_filter($config['items'] ?? [], fn ($item) => is_array($item) && filled($item['photo_id'] ?? null)));
        $photos = Photo::whereKey(array_column($items, 'photo_id'))->get()->keyBy('id');

        return collect($items)
            ->filter(fn (array $item) => $photos->has((int) $item['photo_id']))
            ->map(fn (array $item) => ['photo' => $photos[(int) $item['photo_id']], 'caption' => filled($item['caption'] ?? null) ? $item['caption'] : null])
            ->take(3)
            ->values()
            ->all();
    }
}

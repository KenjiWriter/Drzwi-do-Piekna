<?php

namespace App\Filament\RichContent;

use App\Filament\Forms\PhotoSelect;
use App\Models\Photo;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Blok „Zdjęcie" w treści wpisu: zdjęcie z biblioteki z podpisem, na całą szerokość tekstu albo węższe.
 * W bazie zostaje tylko identyfikator zdjęcia, więc wersje na telefon i komputer (srcset) oraz opis
 * alternatywny zawsze pochodzą z biblioteki.
 */
class PhotoBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'photo';
    }

    public static function getLabel(): string
    {
        return 'Zdjęcie';
    }

    public static function getIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedPhoto;
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Zdjęcie w tekście')
            ->modalDescription('Wybierz zdjęcie z biblioteki albo wgraj nowe. Opis alternatywny (dla osób niewidomych i Google) pochodzi ze zdjęcia, więc uzupełnij go w bibliotece.')
            ->modalSubmitActionLabel('Wstaw zdjęcie')
            ->schema([
                PhotoSelect::standalone()->required(),
                TextInput::make('caption')
                    ->label('Podpis pod zdjęciem')
                    ->maxLength(200)
                    ->helperText('Opcjonalny. Wpisz też źródło, jeśli zdjęcie nie jest własne.'),
                Select::make('size')
                    ->label('Rozmiar')
                    ->options(['wide' => 'Na całą szerokość tekstu', 'narrow' => 'Węższe, wyśrodkowane'])
                    ->default('wide')
                    ->required()
                    ->native(false)
                    ->helperText('Zdjęcia pionowe (portrety) lepiej wyglądają jako węższe, poziome na całą szerokość.'),
            ]);
    }

    /** @param  array<string, mixed>  $config */
    public static function getPreviewLabel(array $config): string
    {
        return ($photo = self::photo($config)) ? "Zdjęcie: {$photo->title}" : 'Zdjęcie (nie znaleziono w bibliotece)';
    }

    /** @param  array<string, mixed>  $config */
    public static function toPreviewHtml(array $config): ?string
    {
        $photo = self::photo($config);

        if (! $photo) {
            return null;
        }

        $caption = filled($config['caption'] ?? null) ? '<figcaption style="margin-top:0.5rem;font-size:0.875rem;opacity:0.7">'.e($config['caption']).'</figcaption>' : '';

        return '<figure style="margin:0"><img src="'.e($photo->url(480)).'" alt="'.e($photo->alt).'" style="display:block;max-width:100%;max-height:18rem;border-radius:0.25rem">'.$caption.'</figure>';
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): ?string
    {
        $photo = self::photo($config);

        // Zdjęcie usunięte z biblioteki nie zostawia pustej ramki na stronie.
        if (! $photo) {
            return '';
        }

        $size = ($config['size'] ?? 'wide') === 'narrow' ? 'narrow' : 'wide';
        $caption = filled($config['caption'] ?? null) ? '<figcaption>'.e($config['caption']).'</figcaption>' : '';

        return '<figure class="ddp-figure ddp-figure--'.$size.'">'.self::image($photo, $size === 'narrow' ? '(min-width: 1024px) 448px, 90vw' : '(min-width: 1024px) 720px, 100vw').$caption.'</figure>';
    }

    /** @param  array<string, mixed>  $config */
    private static function photo(array $config): ?Photo
    {
        return filled($config['photo_id'] ?? null) ? Photo::find($config['photo_id']) : null;
    }

    /** Znacznik zdjęcia z biblioteki: wymiary (bez skoku układu), srcset i kolor tła na czas ładowania. */
    public static function image(Photo $photo, string $sizes): string
    {
        $image = $photo->toImage();

        return '<img src="'.e($image['src']).'" srcset="'.e($image['srcset']).'" sizes="'.e($sizes).'" width="'.$image['width'].'" height="'.$image['height'].'" alt="'.e($image['alt']).'" loading="lazy" decoding="async"'
            .($image['color'] ? ' style="background-color:'.e($image['color']).'"' : '').'>';
    }
}

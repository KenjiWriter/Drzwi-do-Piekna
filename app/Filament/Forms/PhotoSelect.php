<?php

namespace App\Filament\Forms;

use App\Filament\Resources\Photos\Schemas\PhotoForm;
use App\Models\Photo;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Storage;

/** Wybór zdjęcia z biblioteki (z miniaturą) albo wgranie nowego prosto z formularza obszaru, zabiegu lub osoby. */
class PhotoSelect
{
    public static function make(string $name = 'photo_id'): Select
    {
        return Select::make($name)
            ->label('Zdjęcie')
            ->relationship('photo', 'title')
            ->getOptionLabelFromRecordUsing(fn (Photo $photo) => self::option($photo))
            ->allowHtml()
            ->searchable(['title', 'alt'])
            ->preload()
            ->placeholder('Bez zdjęcia')
            ->createOptionForm(PhotoForm::uploadFields())
            ->createOptionUsing(fn (array $data) => PhotoForm::store($data)->getKey())
            ->createOptionAction(fn (Action $action) => $action
                ->label('Wgraj nowe zdjęcie')
                ->modalHeading('Wgraj zdjęcie')
                ->modalSubmitActionLabel('Wgraj'));
    }

    /**
     * Ten sam wybór zdjęcia, ale bez relacji z modelem formularza. Służy do bloków wewnątrz edytora tekstu,
     * gdzie wybrany identyfikator ląduje w ustawieniach bloku, a nie w kolumnie rekordu.
     */
    public static function standalone(string $name = 'photo_id'): Select
    {
        return Select::make($name)
            ->label('Zdjęcie')
            ->allowHtml()
            ->searchable()
            ->placeholder('Wybierz zdjęcie z biblioteki')
            ->options(fn () => self::options(Photo::query()->latest('id')->limit(50)->get()))
            ->getSearchResultsUsing(fn (string $search) => self::options(Photo::query()
                ->where(fn ($query) => $query->whereLike('title', "%{$search}%")->orWhereLike('alt', "%{$search}%"))
                ->latest('id')->limit(50)->get()))
            ->getOptionLabelUsing(fn ($value) => ($photo = Photo::find($value)) ? self::option($photo) : null)
            ->createOptionForm(PhotoForm::uploadFields())
            ->createOptionUsing(fn (array $data) => PhotoForm::store($data)->getKey())
            ->createOptionAction(fn (Action $action) => $action
                ->label('Wgraj nowe zdjęcie')
                ->modalHeading('Wgraj zdjęcie')
                ->modalSubmitActionLabel('Wgraj'));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Photo>  $photos
     * @return array<int, string> identyfikator => miniatura z nazwą
     */
    private static function options($photos): array
    {
        return $photos->mapWithKeys(fn (Photo $photo) => [$photo->getKey() => self::option($photo)])->all();
    }

    /** Miniatura i nazwa. Nazwa jest od użytkownika, więc przechodzi przez e(). */
    private static function option(Photo $photo): string
    {
        $thumbnail = e(Storage::disk(Photo::DISK)->url($photo->thumbnail_path));

        return '<span style="display:flex;align-items:center;gap:0.75rem">'
            .'<img src="'.$thumbnail.'" alt="" style="width:2.5rem;height:2.5rem;object-fit:cover;border-radius:0.25rem">'
            .'<span>'.e($photo->title).'</span></span>';
    }
}

<?php

namespace App\Filament\Resources\Photos\Schemas;

use App\Exceptions\AltTextUnavailable;
use App\Exceptions\InvalidPhoto;
use App\Models\Photo;
use App\Support\AltText;
use App\Support\PhotoStore;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PhotoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Zdjęcie')
                ->schema([
                    self::fileField()->hiddenOn('edit'),
                    Image::make(fn (?Photo $record) => $record?->url(960) ?? '', fn (?Photo $record) => $record?->alt ?? '')
                        // Domyślna wysokość to stałe 8rem, które zniekształca zdjęcia; tu zachowujemy proporcje.
                        ->imageWidth('min(100%, 28rem)')
                        ->imageHeight('auto')
                        ->visibleOn('edit'),
                    self::altField(),
                    self::titleField()
                        ->required(fn (string $operation) => $operation === 'edit')
                        ->helperText(fn (string $operation) => $operation === 'edit'
                            ? 'Tylko dla Ciebie, żeby łatwo znaleźć zdjęcie na liście. Nie widać jej na stronie.'
                            : 'Puste: nazwa pliku.'),
                ]),
        ]);
    }

    /**
     * Pola wgrywania nowego zdjęcia w oknie „Wgraj nowe” przy wyborze zdjęcia (obszar, zabieg, zespół).
     *
     * @return array<int, Component>
     */
    public static function uploadFields(): array
    {
        return [self::fileField(), self::altField(), self::titleField()];
    }

    /**
     * Plik zostaje na dysku tymczasowym (storeFiles(false)), a do biblioteki trafia dopiero po obróbce w {@see store()}.
     */
    private static function fileField(): FileUpload
    {
        return FileUpload::make('file')
            ->label('Plik zdjęcia')
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(12 * 1024)
            ->storeFiles(false)
            ->imageEditor()
            ->required()
            ->helperText('JPEG, PNG lub WebP, do 12 MB, co najmniej '.PhotoStore::MIN_WIDTH.' px szerokości. Strona sama przygotuje wersje na telefon i komputer. Metadane (np. miejsce zrobienia zdjęcia) są usuwane.');
    }

    /**
     * Opis alternatywny (WCAG 2.1, 1.1.1). Z kluczem Anthropic API przycisk przy polu proponuje opis, a przy wgrywaniu
     * pole może zostać puste: opis napisze AI ({@see store()}). Przy edycji opis jest zawsze wymagany.
     * `$record` bywa tu rekordem innego modelu (okno „Wgraj nowe zdjęcie” w formularzu zabiegu), stąd instanceof.
     */
    private static function altField(): TextInput
    {
        return TextInput::make('alt')
            ->label('Opis zdjęcia')
            ->required(fn ($record) => $record instanceof Photo || ! AltText::enabled())
            ->maxLength(AltText::MAX_LENGTH)
            ->suffixAction(self::describeAction())
            ->helperText(fn ($record) => 'Jednym zdaniem: co widać na zdjęciu, np. „Lekarka w gabinecie przy fotelu zabiegowym”. Czytają to osoby niewidome i wyszukiwarki.'
                .(! AltText::enabled() ? '' : ($record instanceof Photo
                    ? ' Przycisk z gwiazdkami zaproponuje opis (AI); przeczytaj go przed zapisem.'
                    : ' Przycisk z gwiazdkami zaproponuje opis (AI), a puste pole AI uzupełni samo przy wgrywaniu.')));
    }

    /** Wysyła pomniejszoną kopię zdjęcia do AI i wpisuje zaproponowany opis do pola (redaktorka może go poprawić przed zapisem). */
    private static function describeAction(): Action
    {
        return Action::make('describeWithAi')
            ->label('Zaproponuj opis (AI)')
            ->tooltip('Zaproponuj opis (AI)')
            ->icon(Heroicon::OutlinedSparkles)
            ->visible(fn () => AltText::enabled())
            ->action(function (Get $get, Set $set, $record): void {
                $file = self::sourceFile($get('file'), $record);

                if ($file === null) {
                    Notification::make()->warning()->title('Najpierw wybierz plik zdjęcia.')->send();

                    return;
                }

                try {
                    $set('alt', app(AltText::class)->describe($file));
                    Notification::make()->success()->title('AI zaproponowało opis')->body('Przeczytaj go i popraw, jeśli czegoś brakuje.')->send();
                } catch (AltTextUnavailable $e) {
                    Notification::make()->danger()->title('Nie udało się zaproponować opisu')->body($e->getMessage())->send();
                }
            });
    }

    /** Plik do opisu: świeżo wybrany w formularzu albo wariant zdjęcia, które już jest w bibliotece. */
    private static function sourceFile(mixed $upload, mixed $record): ?string
    {
        $upload = Arr::first(Arr::wrap($upload));

        if ($upload instanceof TemporaryUploadedFile) {
            return $upload->getRealPath();
        }

        return $record instanceof Photo ? $record->localPath() : null;
    }

    private static function titleField(): TextInput
    {
        return TextInput::make('title')
            ->label('Nazwa')
            ->maxLength(255)
            ->placeholder('Puste: nazwa pliku');
    }

    /**
     * Zapisuje wgrany plik w bibliotece. Błąd pliku (nie zdjęcie, za małe, za duże) pokazuje redaktorce
     * i przerywa zapis, zostawiając formularz otwarty.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Halt
     */
    public static function store(array $data): Photo
    {
        $upload = Arr::first(Arr::wrap($data['file'] ?? null));

        if (! $upload instanceof TemporaryUploadedFile) {
            Notification::make()->danger()->title('Wybierz plik zdjęcia.')->send();

            throw new Halt;
        }

        $title = filled($data['title'] ?? null)
            ? $data['title']
            : Str::of($upload->getClientOriginalName())->beforeLast('.')->replace(['-', '_'], ' ')->squish()->toString();

        // Puste pole opisu (dozwolone tylko z kluczem AI): opis proponuje AI. Gdy się nie uda, formularz zostaje otwarty z plikiem.
        $alt = trim((string) ($data['alt'] ?? ''));
        $byAi = $alt === '';

        if ($byAi) {
            try {
                $alt = app(AltText::class)->describe($upload->getRealPath());
            } catch (AltTextUnavailable $e) {
                Notification::make()->danger()->title('Wpisz opis zdjęcia')->body($e->getMessage())->send();

                throw new Halt;
            }
        }

        try {
            $photo = app(PhotoStore::class)->store($upload->getRealPath(), $title, $alt, $upload->getClientOriginalName());

            if ($byAi) {
                Notification::make()->success()->title('Opis zdjęcia napisało AI')->body("„{$alt}”. Sprawdź go i popraw w razie potrzeby.")->send();
            }

            return $photo;
        } catch (InvalidPhoto $e) {
            Notification::make()->danger()->title('Nie wgrano zdjęcia')->body($e->getMessage())->send();

            throw new Halt;
        } finally {
            $upload->delete();
        }
    }
}

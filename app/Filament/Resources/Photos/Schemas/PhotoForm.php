<?php

namespace App\Filament\Resources\Photos\Schemas;

use App\Exceptions\InvalidPhoto;
use App\Models\Photo;
use App\Support\PhotoStore;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
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
     * @return array<int, \Filament\Schemas\Components\Component>
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

    private static function altField(): TextInput
    {
        return TextInput::make('alt')
            ->label('Opis zdjęcia')
            ->required()
            ->maxLength(255)
            ->helperText('Jednym zdaniem: co widać na zdjęciu, np. „Lekarka w gabinecie przy fotelu zabiegowym”. Czytają to osoby niewidome i wyszukiwarki.');
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

        try {
            return app(PhotoStore::class)->store($upload->getRealPath(), $title, $data['alt'], $upload->getClientOriginalName());
        } catch (InvalidPhoto $e) {
            Notification::make()->danger()->title('Nie wgrano zdjęcia')->body($e->getMessage())->send();

            throw new Halt;
        } finally {
            $upload->delete();
        }
    }
}

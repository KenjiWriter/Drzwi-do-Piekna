<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\Forms\PhotoSelect;
use App\Support\PostContent;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Wpis')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Tytuł')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state ?? ''));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Adres strony')
                            ->required()
                            ->maxLength(255)
                            ->regex('/^[a-z0-9-]+$/')
                            ->unique(ignoreRecord: true)
                            ->prefix('/blog/')
                            ->helperText('Małe litery, cyfry i myślniki. Zmiana adresu opublikowanego wpisu zrywa linki i pozycję w Google, a stary adres nie przekierowuje sam.'),
                        Textarea::make('excerpt')
                            ->label('Zajawka')
                            ->required()
                            ->rows(3)
                            ->maxLength(400)
                            ->helperText('Widoczna na liście wpisów i pod tytułem. Jest też domyślnym opisem w Google.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Treść')
                    ->schema([
                        RichEditor::make('body')
                            ->label('Treść wpisu')
                            ->required()
                            // Zdjęcia wstawia się blokiem „Zdjęcie" z biblioteki (opis alternatywny, wersje na telefon, kontrola użycia),
                            // a nie wgrywaniem plików prosto do tekstu.
                            ->fileAttachments(false)
                            ->customBlocks(PostContent::blocks())
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'link'],
                                ['h2', 'h3'],
                                ['alignStart', 'alignCenter', 'alignEnd'],
                                ['blockquote', 'bulletList', 'orderedList', 'horizontalRule'],
                                ['table', 'customBlocks'],
                                ['undo', 'redo'],
                            ])
                            ->extraInputAttributes(['style' => 'min-height: 28rem'])
                            ->helperText('Tytuł wpisu jest już nad treścią, więc śródtytuły to H2 i H3. Zdjęcia wstawisz przyciskiem „Bloki” (Zdjęcie albo Zdjęcia obok siebie), a tabelę przyciskiem tabeli. Bez nazw handlowych leków na receptę i bez obietnic wyniku.'),
                    ]),
                Section::make('Autor i zdjęcie')
                    ->columns(2)
                    ->schema([
                        TextInput::make('author_name')
                            ->label('Autor')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Wpisz imię i nazwisko, także osoby spoza zespołu na stronie.'),
                        TextInput::make('author_role')
                            ->label('Rola autora')
                            ->maxLength(255)
                            ->placeholder('np. Kosmetolog, Drzwi do Piękna'),
                        PhotoSelect::make()
                            ->label('Zdjęcie wpisu')
                            ->helperText('Widoczne na liście wpisów, nad treścią i w podglądzie linku. Wybierz zdjęcie, do którego gabinet ma prawa, i dodaj opis alternatywny w bibliotece.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Publikacja')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Opublikowany')
                            ->live()
                            ->helperText('Szkic nie jest widoczny na stronie ani w mapie strony.'),
                        DateTimePicker::make('published_at')
                            ->label('Data publikacji')
                            ->seconds(false)
                            ->default(now())
                            ->required(fn (Get $get) => (bool) $get('is_published'))
                            ->helperText('Przyszła data planuje publikację: wpis pojawi się sam, gdy ta data nadejdzie.'),
                    ]),
                Section::make('Wyszukiwarki (SEO)')
                    ->description('Puste pola oznaczają teksty wygenerowane automatycznie z tytułu i zajawki.')
                    ->collapsed()
                    ->schema([
                        TextInput::make('meta_title')
                            ->label('Tytuł w Google')
                            ->maxLength(70)
                            ->helperText('Do 60 znaków mieści się w wynikach w całości.'),
                        Textarea::make('meta_description')
                            ->label('Opis w Google')
                            ->rows(2)
                            ->maxLength(160),
                    ]),
            ]);
    }
}

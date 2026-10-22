<?php

namespace App\Filament\Resources\TreatmentCategories\Schemas;

use App\Filament\Forms\PhotoSelect;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TreatmentCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Obszar')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nazwa')
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state ?? ''));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Adres strony')
                            ->required()
                            ->maxLength(100)
                            ->regex('/^[a-z0-9-]+$/')
                            ->unique(ignoreRecord: true)
                            ->prefix('/zabiegi/')
                            ->helperText('Małe litery, cyfry i myślniki. Zmiana adresu opublikowanego obszaru zrywa linki i pozycję w Google.'),
                        Textarea::make('summary')
                            ->label('Krótki opis (na kaflu)')
                            ->required()
                            ->rows(2)
                            ->maxLength(160)
                            ->columnSpanFull(),
                        Textarea::make('intro')
                            ->label('Wstęp na stronie obszaru')
                            ->rows(3)
                            ->columnSpanFull(),
                        PhotoSelect::make()
                            ->label('Zdjęcie kafla')
                            ->placeholder('Zdjęcie demonstracyjne')
                            ->helperText('Wybierz z biblioteki albo wgraj nowe. Puste: strona pokazuje zdjęcie demonstracyjne.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Publikacja')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Opublikowany')
                            ->default(true)
                            ->helperText('Ukryty obszar znika ze strony, z menu i z mapy strony.'),
                        TextInput::make('sort_order')
                            ->label('Kolejność')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Mniejsza liczba oznacza wyżej. Kolejność zmienisz też przeciągając wiersze na liście.'),
                    ]),
                Section::make('Wyszukiwarki (SEO)')
                    ->description('Puste pola oznaczają teksty wygenerowane automatycznie z nazwy i opisu.')
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

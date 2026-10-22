<?php

namespace App\Filament\Resources\Treatments\Schemas;

use App\Filament\Forms\PhotoSelect;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TreatmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Zabieg')
                    ->columns(2)
                    ->schema([
                        Select::make('treatment_category_id')
                            ->label('Obszar')
                            ->relationship('category', 'name')
                            ->required()
                            ->preload()
                            ->searchable(),
                        TextInput::make('name')
                            ->label('Nazwa')
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
                            ->helperText('Małe litery, cyfry i myślniki. Zmiana adresu opublikowanego zabiegu zrywa linki i pozycję w Google.'),
                        TextInput::make('duration')
                            ->label('Czas zabiegu')
                            ->maxLength(255)
                            ->placeholder('np. od kilkunastu minut do godziny'),
                        Textarea::make('summary')
                            ->label('Krótki opis')
                            ->required()
                            ->rows(2)
                            ->maxLength(300)
                            ->helperText('Widoczny na liście zabiegów i pod tytułem strony.')
                            ->columnSpanFull(),
                        PhotoSelect::make()
                            ->helperText('Widoczne na stronie zabiegu i w podglądzie linku (Facebook, Google). Zdjęcia przed i po tylko za pisemną zgodą pacjentki.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Treść strony zabiegu')
                    ->schema([
                        Textarea::make('description')
                            ->label('Opis')
                            ->rows(8)
                            ->helperText('Akapity oddziel pustą linią. Bez nazw handlowych leków na receptę.'),
                        Repeater::make('indications')
                            ->label('Wskazania')
                            ->simple(TextInput::make('item')->label('Wskazanie')->required()->maxLength(250))
                            ->addActionLabel('Dodaj wskazanie')
                            ->defaultItems(0)
                            ->reorderable(),
                        Repeater::make('effects')
                            ->label('Efekty')
                            ->simple(TextInput::make('item')->label('Efekt')->required()->maxLength(250))
                            ->addActionLabel('Dodaj efekt')
                            ->defaultItems(0)
                            ->reorderable(),
                    ]),
                Section::make('Publikacja')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Opublikowany')
                            ->default(true)
                            ->helperText('Ukryty zabieg znika ze strony, z wyszukiwania i z mapy strony.'),
                        TextInput::make('sort_order')
                            ->label('Kolejność')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Kolejność w obrębie obszaru. Zmienisz ją też przeciągając wiersze na liście.'),
                    ]),
                Section::make('Wyszukiwarki (SEO)')
                    ->description('Puste pola oznaczają teksty wygenerowane automatycznie z nazwy i krótkiego opisu.')
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
                Section::make('Adres ze starej strony')
                    ->description('Dzięki niemu stary adres drzwidopiekna.pl/zabieg/... przekierowuje na tę stronę (301). Nie zmieniaj bez potrzeby.')
                    ->collapsed()
                    ->schema([
                        TextInput::make('legacy_slug')
                            ->label('Stary adres (część po /zabieg/)')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ]),
            ]);
    }
}

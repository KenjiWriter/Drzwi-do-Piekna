<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Closure;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Opinia')
                    ->columns(2)
                    ->schema([
                        TextInput::make('author')
                            ->label('Podpis')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Imię albo imię i inicjał, np. „Anna” lub „Anna K.”. Nazwisko tylko za wyraźną zgodą.'),
                        TextInput::make('source')
                            ->label('Źródło')
                            ->maxLength(100)
                            ->placeholder('np. ZnanyLekarz, Google')
                            ->helperText('Pokazuje się obok podpisu.'),
                        Textarea::make('quote')
                            ->label('Treść opinii')
                            ->required()
                            ->rows(4)
                            ->maxLength(600)
                            ->helperText('Dokładne słowa pacjentki, bez poprawiania sensu. Cudzysłów doda strona.')
                            ->columnSpanFull(),
                        TextInput::make('source_url')
                            ->label('Link do opinii w źródle')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make('Zgoda i publikacja')
                    ->description('Opinia pokazuje się na stronie głównej tylko wtedy, gdy jest opublikowana i ma potwierdzoną zgodę pacjentki. Zasady reklamy działalności leczniczej do potwierdzenia z prawnikiem gabinetu.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('consent_confirmed')
                            ->label('Mam zgodę pacjentki na publikację tej opinii')
                            ->live()
                            ->afterStateUpdated(function (bool $state, Get $get, Set $set): void {
                                // Cofnięcie zgody od razu zdejmuje opinię ze strony.
                                if (! $state && ! $get('is_demo')) {
                                    $set('is_published', false);
                                }
                            }),
                        Toggle::make('is_published')
                            ->label('Pokaż na stronie głównej')
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    if ($value && ! $get('consent_confirmed') && ! $get('is_demo')) {
                                        $fail('Opinii bez zgody pacjentki nie można opublikować.');
                                    }
                                },
                            ]),
                        Toggle::make('is_demo')
                            ->label('Opinia przykładowa (demo)')
                            ->live()
                            ->helperText('Tylko do pokazania układu. Strona dołącza wtedy uwagę „Przykładowa opinia”. Zastąp ją prawdziwą i wyłącz.'),
                        TextInput::make('sort_order')
                            ->label('Kolejność')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Mniejsza liczba oznacza wyżej. Zmienisz ją też przeciągając wiersze na liście.'),
                    ]),
            ]);
    }
}

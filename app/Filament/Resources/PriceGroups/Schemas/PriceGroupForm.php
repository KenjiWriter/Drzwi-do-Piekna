<?php

namespace App\Filament\Resources\PriceGroups\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PriceGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Grupa cennika')
                    ->description('Pozycje z cenami dodajesz niżej, po zapisaniu grupy.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nazwa grupy')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state ?? ''));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Identyfikator (kotwica)')
                            ->required()
                            ->maxLength(255)
                            ->regex('/^[a-z0-9-]+$/')
                            ->unique(ignoreRecord: true)
                            ->helperText('Używany w linku do grupy, np. /cennik#laseroterapia.'),
                        Textarea::make('note')
                            ->label('Uwaga pod tytułem grupy')
                            ->rows(2)
                            ->helperText('Np. „Zakres zabiegu ustalamy indywidualnie po konsultacji”.')
                            ->columnSpanFull(),
                        TextInput::make('sort_order')
                            ->label('Kolejność')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0),
                    ]),
            ]);
    }
}

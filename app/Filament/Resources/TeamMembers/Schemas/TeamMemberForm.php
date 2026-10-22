<?php

namespace App\Filament\Resources\TeamMembers\Schemas;

use App\Filament\Forms\PhotoSelect;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TeamMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Osoba')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Imię i nazwisko')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state ?? ''));
                                }
                            })
                            ->helperText('Razem z tytułem, np. „dr Anita Symonowicz”. Monogram powstaje z inicjałów.'),
                        TextInput::make('slug')
                            ->label('Identyfikator')
                            ->required()
                            ->maxLength(255)
                            ->regex('/^[a-z0-9-]+$/')
                            ->unique(ignoreRecord: true),
                        TextInput::make('role')
                            ->label('Rola')
                            ->maxLength(255)
                            ->placeholder('np. lekarz stomatolog, medycyna estetyczna')
                            ->columnSpanFull(),
                        PhotoSelect::make()
                            ->helperText('Portret w pionie. Bez zdjęcia strona pokazuje monogram z inicjałów. Pamiętaj o zgodzie osoby na publikację wizerunku.')
                            ->columnSpanFull(),
                        Textarea::make('bio')
                            ->label('Opis')
                            ->rows(5)
                            ->helperText('Kilka zdań o wykształceniu i podejściu do pracy.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Publikacja')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Pokaż na stronie „O mnie”')
                            ->default(true)
                            ->helperText('Nie publikuj osoby, o której nie wiadomo jeszcze, jaką pełni rolę w gabinecie.'),
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

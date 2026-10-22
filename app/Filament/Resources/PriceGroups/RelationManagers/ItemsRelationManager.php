<?php

namespace App\Filament\Resources\PriceGroups\RelationManagers;

use App\Models\PriceItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Pozycje cennika';

    protected static ?string $modelLabel = 'pozycja';

    protected static ?string $pluralModelLabel = 'pozycje';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('subgroup')
                    ->label('Podgrupa')
                    ->maxLength(255)
                    // reorder(): relacja jest posortowana, a DISTINCT z ORDER BY po innej kolumnie nie działa ani w MySQL, ani w PostgreSQL.
                    ->datalist(fn () => $this->getOwnerRecord()->items()->reorder()->whereNotNull('subgroup')->distinct()->pluck('subgroup')->all())
                    ->helperText('Nagłówek nad pozycjami, np. „SpaDeep”. Pozycje z tą samą podgrupą stoją razem.')
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label('Nazwa')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('price_min')
                    ->label('Cena (zł)')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->suffix('zł')
                    ->helperText('Puste: „Cena po konsultacji” albo tylko cena pakietu.'),
                TextInput::make('price_max')
                    ->label('Cena do (zł)')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->gte('price_min')
                    ->suffix('zł')
                    ->helperText('Tylko dla zakresu, np. 500–700.'),
                Toggle::make('is_from')
                    ->label('Cena „od”')
                    ->columnSpanFull(),
                TextInput::make('pack_price')
                    ->label('Cena pakietu (zł)')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->suffix('zł'),
                TextInput::make('pack_label')
                    ->label('Opis pakietu')
                    ->maxLength(255)
                    ->placeholder('np. 4 zabiegi'),
                TextInput::make('note')
                    ->label('Uwaga przy pozycji')
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->label('Kolejność')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(fn () => ($this->getOwnerRecord()->items()->reorder()->max('sort_order') ?? 0) + 1)
                    ->helperText('Zmienisz ją też przeciągając wiersze na liście.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('subgroup')
                    ->label('Podgrupa')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Nazwa')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('price')
                    ->label('Cena')
                    ->state(fn (PriceItem $record) => $record->priceLabel())
                    ->description(fn (PriceItem $record) => $record->pack_price && $record->price_min !== null
                        ? trim(($record->pack_label ?: 'Pakiet').': '.number_format($record->pack_price, 0, ',', ' ').' zł')
                        : null),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

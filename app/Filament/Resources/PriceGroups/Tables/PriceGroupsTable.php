<?php

namespace App\Filament\Resources\PriceGroups\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PriceGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Grupa')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('items_count')
                    ->label('Pozycje')
                    ->counts('items')
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Usunie też wszystkie pozycje cennika w tej grupie. Tej operacji nie da się cofnąć.'),
            ]);
    }
}

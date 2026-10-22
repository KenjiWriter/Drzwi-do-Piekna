<?php

namespace App\Filament\Resources\Photos\Tables;

use App\Models\Photo;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PhotosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['categories', 'treatments', 'teamMembers', 'posts', 'inlinePosts']))
            ->defaultSort('created_at', 'desc')
            ->checkIfRecordIsSelectableUsing(fn (Photo $record) => ! $record->isUsed())
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('')
                    ->disk(Photo::DISK)
                    ->imageSize(64)
                    ->extraImgAttributes(['alt' => '']),
                TextColumn::make('title')
                    ->label('Nazwa')
                    ->searchable()
                    ->description(fn (Photo $record) => $record->alt),
                TextColumn::make('size')
                    ->label('Wymiary')
                    ->state(fn (Photo $record) => "{$record->width} × {$record->height} px")
                    ->toggleable(),
                TextColumn::make('usage')
                    ->label('Użyte')
                    ->state(fn (Photo $record) => $record->usageCount())
                    ->badge()
                    ->color(fn (int $state) => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (int $state) => $state ? "{$state}×" : 'Nigdzie'),
                TextColumn::make('created_at')
                    ->label('Dodano')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('unused')
                    ->label('Nieużywane')
                    ->query(fn (Builder $query) => $query
                        ->whereDoesntHave('categories')
                        ->whereDoesntHave('treatments')
                        ->whereDoesntHave('teamMembers')
                        ->whereDoesntHave('posts')
                        ->whereDoesntHave('inlinePosts')),
            ])
            ->recordActions([
                EditAction::make(),
                // Użytego zdjęcia nie usuwamy: z obszaru, zabiegu lub osoby zniknęłoby po cichu.
                DeleteAction::make()->hidden(fn (Photo $record) => $record->isUsed()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

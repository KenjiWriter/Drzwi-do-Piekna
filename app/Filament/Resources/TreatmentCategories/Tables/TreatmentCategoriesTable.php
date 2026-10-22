<?php

namespace App\Filament\Resources\TreatmentCategories\Tables;

use App\Models\Photo;
use App\Models\TreatmentCategory;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TreatmentCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('photo.thumbnail_path')
                    ->label('')
                    ->disk(Photo::DISK)
                    ->imageSize(48)
                    ->extraImgAttributes(['alt' => '']),
                TextColumn::make('name')
                    ->label('Nazwa')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Adres')
                    ->prefix('/zabiegi/')
                    ->color('gray'),
                TextColumn::make('treatments_count')
                    ->label('Zabiegi')
                    ->counts('treatments')
                    ->badge(),
                ToggleColumn::make('is_published')
                    ->label('Opublikowany'),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Opublikowany'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Zobacz na stronie')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (TreatmentCategory $record) => url('/zabiegi/'.$record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn (TreatmentCategory $record) => $record->is_published),
                EditAction::make(),
                // Usunięcie obszaru kasuje jego zabiegi, więc dozwolone tylko dla pustego obszaru.
                DeleteAction::make()
                    ->hidden(fn (TreatmentCategory $record) => $record->treatments()->exists()),
            ]);
    }
}

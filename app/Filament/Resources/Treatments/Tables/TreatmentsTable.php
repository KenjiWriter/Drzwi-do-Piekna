<?php

namespace App\Filament\Resources\Treatments\Tables;

use App\Models\Treatment;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class TreatmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Nazwa')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label('Obszar')
                    ->badge()
                    ->color('gray'),
                ToggleColumn::make('is_published')
                    ->label('Opublikowany'),
            ])
            ->filters([
                SelectFilter::make('treatment_category_id')
                    ->label('Obszar')
                    ->relationship('category', 'name'),
                TernaryFilter::make('is_published')
                    ->label('Opublikowany'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Zobacz na stronie')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Treatment $record) => url($record->url()))
                    ->openUrlInNewTab()
                    ->visible(fn (Treatment $record) => $record->is_published && $record->category->is_published),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Opublikuj zaznaczone')
                        ->icon(Heroicon::OutlinedEye)
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')
                        ->label('Ukryj zaznaczone')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

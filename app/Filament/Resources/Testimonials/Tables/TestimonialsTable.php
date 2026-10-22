<?php

namespace App\Filament\Resources\Testimonials\Tables;

use App\Models\Testimonial;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TestimonialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('author')
                    ->label('Podpis')
                    ->searchable()
                    ->description(fn (Testimonial $record) => $record->source),
                TextColumn::make('quote')
                    ->label('Opinia')
                    ->limit(90)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('is_demo')
                    ->label('Rodzaj')
                    ->badge()
                    ->state(fn (Testimonial $record) => $record->is_demo ? 'Przykładowa' : null)
                    ->color('warning'),
                IconColumn::make('consent_confirmed')
                    ->label('Zgoda')
                    ->boolean(),
                // Bez zgody przełącznik jest zablokowany: opinię można opublikować dopiero po jej potwierdzeniu.
                ToggleColumn::make('is_published')
                    ->label('Na stronie')
                    ->disabled(fn (Testimonial $record) => ! $record->consent_confirmed && ! $record->is_demo)
                    ->tooltip(fn (Testimonial $record) => ! $record->consent_confirmed && ! $record->is_demo ? 'Najpierw potwierdź zgodę pacjentki w edycji opinii.' : null),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Na stronie'),
                TernaryFilter::make('consent_confirmed')->label('Zgoda pacjentki'),
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

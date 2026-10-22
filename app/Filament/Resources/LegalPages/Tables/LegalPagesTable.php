<?php

namespace App\Filament\Resources\LegalPages\Tables;

use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LegalPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->label('Dokument')
                    ->description(fn (Page $record) => '/'.$record->slug),
                TextColumn::make('status')
                    ->label('Stan')
                    ->state(fn (Page $record) => $record->isDraft() ? 'Wersja robocza' : 'Obowiązuje')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Obowiązuje' ? 'success' : 'warning'),
                TextColumn::make('effective_from')
                    ->label('Obowiązuje od')
                    ->date('d.m.Y')
                    ->placeholder('Brak daty'),
                TextColumn::make('updated_at')
                    ->label('Zmieniono')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Zobacz na stronie')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Page $record) => '/'.$record->slug, shouldOpenInNewTab: true),
                EditAction::make(),
            ]);
    }
}

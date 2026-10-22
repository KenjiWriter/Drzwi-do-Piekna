<?php

namespace App\Filament\Resources\ContactRequests\Tables;

use App\Enums\ContactStatus;
use App\Models\ContactRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ContactRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Imię')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('subject')
                    ->label('Obszar')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                IconColumn::make('notified_at')
                    ->label('E-mail do gabinetu')
                    ->boolean()
                    ->state(fn (ContactRequest $record) => $record->notified_at !== null)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ContactStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('markDone')
                    ->label('Załatwione')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (ContactRequest $record) => $record->status !== ContactStatus::Done)
                    ->action(fn (ContactRequest $record) => $record->update(['status' => ContactStatus::Done])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markDone')
                        ->label('Oznacz jako załatwione')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->action(fn (Collection $records) => $records->each->update(['status' => ContactStatus::Done]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

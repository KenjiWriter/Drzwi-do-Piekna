<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Models\Photo;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('photo.thumbnail_path')
                    ->label('')
                    ->disk(Photo::DISK)
                    ->imageSize(48)
                    ->extraImgAttributes(['alt' => '']),
                TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable()
                    ->wrap()
                    ->description(fn (Post $record) => $record->author_name),
                TextColumn::make('published_at')
                    ->label('Data publikacji')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Bez daty')
                    ->color(fn (Post $record) => $record->published_at?->isFuture() ? 'warning' : null)
                    ->description(fn (Post $record) => $record->published_at?->isFuture() ? 'Zaplanowany' : null),
                ToggleColumn::make('is_published')
                    ->label('Opublikowany'),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Opublikowany'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Zobacz na stronie')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Post $record) => $record->url(), shouldOpenInNewTab: true)
                    ->visible(fn (Post $record) => $record->is_published && $record->published_at?->isPast()),
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

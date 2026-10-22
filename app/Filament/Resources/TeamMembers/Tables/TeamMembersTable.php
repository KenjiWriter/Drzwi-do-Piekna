<?php

namespace App\Filament\Resources\TeamMembers\Tables;

use App\Models\Photo;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TeamMembersTable
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
                    ->circular()
                    ->imageSize(48)
                    ->extraImgAttributes(['alt' => '']),
                TextColumn::make('name')
                    ->label('Imię i nazwisko')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Rola')
                    ->placeholder('Brak roli')
                    ->wrap(),
                ToggleColumn::make('is_published')
                    ->label('Na stronie'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

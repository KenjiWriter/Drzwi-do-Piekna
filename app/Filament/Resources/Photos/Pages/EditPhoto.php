<?php

namespace App\Filament\Resources\Photos\Pages;

use App\Filament\Resources\Photos\PhotoResource;
use App\Models\Photo;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPhoto extends EditRecord
{
    protected static string $resource = PhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Użytego zdjęcia nie usuwamy, bo zniknęłoby z obszaru, zabiegu lub osoby.
            DeleteAction::make()->hidden(fn (Photo $record) => $record->isUsed()),
        ];
    }
}

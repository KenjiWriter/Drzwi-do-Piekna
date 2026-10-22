<?php

namespace App\Filament\Resources\PriceGroups\Pages;

use App\Filament\Resources\PriceGroups\PriceGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePriceGroup extends CreateRecord
{
    protected static string $resource = PriceGroupResource::class;

    // Po utworzeniu grupy przechodzimy do jej edycji, gdzie dodaje się pozycje.
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}

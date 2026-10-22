<?php

namespace App\Filament\Resources\PriceGroups\Pages;

use App\Filament\Resources\PriceGroups\PriceGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPriceGroups extends ListRecords
{
    protected static string $resource = PriceGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

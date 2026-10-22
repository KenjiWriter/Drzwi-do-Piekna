<?php

namespace App\Filament\Resources\TreatmentCategories\Pages;

use App\Filament\Resources\TreatmentCategories\TreatmentCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTreatmentCategories extends ListRecords
{
    protected static string $resource = TreatmentCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

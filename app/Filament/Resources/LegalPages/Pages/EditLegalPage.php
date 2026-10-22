<?php

namespace App\Filament\Resources\LegalPages\Pages;

use App\Filament\Resources\LegalPages\LegalPageResource;
use App\Models\Page;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditLegalPage extends EditRecord
{
    protected static string $resource = LegalPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('Zobacz na stronie')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (Page $record) => '/'.$record->slug, shouldOpenInNewTab: true),
        ];
    }
}

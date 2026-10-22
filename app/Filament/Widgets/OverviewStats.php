<?php

namespace App\Filament\Widgets;

use App\Enums\ContactStatus;
use App\Filament\Resources\ContactRequests\ContactRequestResource;
use App\Filament\Resources\PriceGroups\PriceGroupResource;
use App\Filament\Resources\Treatments\TreatmentResource;
use App\Models\ContactRequest;
use App\Models\PriceGroup;
use App\Models\PriceItem;
use App\Models\Treatment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Na skróty';

    protected function getStats(): array
    {
        $new = ContactRequest::where('status', ContactStatus::New)->count();
        $published = Treatment::where('is_published', true)->count();

        return [
            Stat::make('Nowe zgłoszenia', $new)
                ->description($new > 0 ? 'Czekają na odpowiedź' : 'Wszystko obsłużone')
                ->color($new > 0 ? 'warning' : 'success')
                ->url(ContactRequestResource::getUrl('index')),
            Stat::make('Opublikowane zabiegi', $published)
                ->description('z '.Treatment::count().' w bazie')
                ->url(TreatmentResource::getUrl('index')),
            Stat::make('Pozycje cennika', PriceItem::count())
                ->description(PriceGroup::count().' grup')
                ->url(PriceGroupResource::getUrl('index')),
        ];
    }
}

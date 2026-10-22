<?php

namespace App\Filament\Resources\ContactRequests\Pages;

use App\Enums\ContactStatus;
use App\Filament\Resources\ContactRequests\ContactRequestResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewContactRequest extends ViewRecord
{
    protected static string $resource = ContactRequestResource::class;

    // Otwarcie nowego zgłoszenia oznacza je jako przeczytane.
    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->status === ContactStatus::New) {
            $this->record->update(['status' => ContactStatus::Read]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markDone')
                ->label('Oznacz jako załatwione')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn () => $this->record->status !== ContactStatus::Done)
                ->action(function (): void {
                    $this->record->update(['status' => ContactStatus::Done]);
                    $this->refreshFormData(['status']);
                }),
            Action::make('markNew')
                ->label('Oznacz jako nowe')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->visible(fn () => $this->record->status !== ContactStatus::New)
                ->action(function (): void {
                    $this->record->update(['status' => ContactStatus::New]);
                    $this->refreshFormData(['status']);
                }),
            DeleteAction::make(),
        ];
    }
}

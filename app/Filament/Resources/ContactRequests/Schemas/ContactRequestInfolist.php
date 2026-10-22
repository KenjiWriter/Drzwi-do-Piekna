<?php

namespace App\Filament\Resources\ContactRequests\Schemas;

use App\Models\ContactRequest;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Zgłoszenie')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Imię'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('phone')
                            ->label('Telefon')
                            ->copyable()
                            ->url(fn (ContactRequest $record) => 'tel:'.preg_replace('/[^\d+]/', '', $record->phone)),
                        TextEntry::make('email')
                            ->label('E-mail')
                            ->placeholder('Nie podano')
                            ->copyable()
                            ->url(fn (ContactRequest $record) => $record->email ? 'mailto:'.$record->email : null),
                        TextEntry::make('subject')->label('Obszar zabiegu')->placeholder('Nie wybrano'),
                        TextEntry::make('created_at')->label('Wysłano')->dateTime('d.m.Y H:i'),
                        TextEntry::make('message')
                            ->label('Wiadomość')
                            ->placeholder('Brak wiadomości')
                            ->prose()
                            ->columnSpanFull(),
                    ]),
                Section::make('Dane techniczne')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('consent_at')->label('Zgoda na kontakt')->dateTime('d.m.Y H:i'),
                        TextEntry::make('notified_at')
                            ->label('Powiadomienie e-mail do gabinetu')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Nie wysłano (sprawdź ustawienia poczty)'),
                        TextEntry::make('confirmation_sent_at')
                            ->label('Potwierdzenie e-mail dla osoby z formularza')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Nie wysłano (nie podano e-maila albo wysyłka się nie udała)'),
                    ]),
            ]);
    }
}

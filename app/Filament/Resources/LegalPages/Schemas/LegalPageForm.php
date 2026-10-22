<?php

namespace App\Filament\Resources\LegalPages\Schemas;

use App\Models\Page;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class LegalPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dokument')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Tytuł')
                        ->required()
                        ->maxLength(255),
                    DatePicker::make('effective_from')
                        ->label('Obowiązuje od')
                        ->native(false)
                        ->displayFormat('d.m.Y')
                        ->required(fn (Get $get) => ! $get('needs_review'))
                        ->helperText('Wymagana po zatwierdzeniu dokumentu. Widoczna u góry strony.'),
                ]),
            Section::make('Treść')
                ->schema([
                    RichEditor::make('body')
                        ->label('Treść dokumentu')
                        ->required()
                        ->fileAttachments(false)
                        ->toolbarButtons([
                            ['bold', 'italic', 'underline', 'link'],
                            ['h2', 'h3'],
                            ['bulletList', 'orderedList', 'blockquote', 'horizontalRule'],
                            ['table'],
                            ['undo', 'redo'],
                        ])
                        ->extraInputAttributes(['style' => 'min-height: 32rem'])
                        ->helperText('Nagłówki H2 tworzą spis treści strony. Sekcja, której nagłówek zawiera słowo „cookies”, dostaje stały adres #cookies (linkuje ją stopka). Fragmenty w nawiasach kwadratowych, np. [NIP], to miejsca do uzupełnienia.'),
                ]),
            Section::make('Zatwierdzenie')
                ->description(fn (?Page $record) => self::status($record))
                ->schema([
                    Toggle::make('needs_review')
                        ->label('Wersja robocza (czeka na zatwierdzenie przez prawnika)')
                        ->live()
                        ->helperText('Dopóki jest włączona, strona pokazuje dopisek „Wersja robocza”, nie trafia do Google (noindex) i blokuje wdrożenie produkcyjne. Wyłącz dopiero, gdy prawnik zatwierdzi treść i nie zostaną żadne pola w nawiasach kwadratowych.')
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                if ($value) {
                                    return;
                                }

                                $left = Page::placeholdersIn((string) $get('body'));

                                if ($left !== []) {
                                    $fail('W treści zostały pola do uzupełnienia: '.implode(', ', array_slice($left, 0, 5)).(count($left) > 5 ? ' i '.(count($left) - 5).' kolejnych' : '').'. Uzupełnij je albo zostaw dokument jako wersję roboczą.');
                                }
                            },
                        ]),
                ]),
        ]);
    }

    private static function status(?Page $record): string
    {
        if (! $record) {
            return '';
        }

        $left = count($record->placeholders());

        return $left
            ? "W zapisanej treści jest jeszcze {$left} ".($left === 1 ? 'pole' : ($left < 5 ? 'pola' : 'pól')).' do uzupełnienia (fragmenty w nawiasach kwadratowych).'
            : 'W zapisanej treści nie ma pól do uzupełnienia.';
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Etap obsługi zgłoszenia z formularza kontaktowego. */
enum ContactStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Read = 'read';
    case Done = 'done';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Nowe',
            self::Read => 'Przeczytane',
            self::Done => 'Załatwione',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Read => 'info',
            self::Done => 'success',
        };
    }
}

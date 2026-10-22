<?php

namespace App\Models;

use App\Support\LegalContent;
use Illuminate\Database\Eloquent\Model;

/** Dokument prawny z treścią edytowaną w panelu (polityka prywatności i cookies). */
class Page extends Model
{
    public const PRIVACY = 'polityka-prywatnosci';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'needs_review' => 'boolean',
        ];
    }

    /**
     * Pola do uzupełnienia w tekście: fragmenty w nawiasach kwadratowych, np. [NIP]. Zasada projektu:
     * w nawiasach kwadratowych stoją miejsca na materiały od klienta. Liczby w nawiasach ([1]) to przypisy, nie pola.
     *
     * @return array<int, string> z nawiasami, bez powtórzeń
     */
    public static function placeholdersIn(?string $text): array
    {
        preg_match_all('/\[[^\]\d][^\]]*\]/u', html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'), $matches);

        return array_values(array_unique($matches[0]));
    }

    /** @return array<int, string> */
    public function placeholders(): array
    {
        return self::placeholdersIn($this->body);
    }

    /**
     * Wersja robocza: oznaczona jako czekająca na zatwierdzenie albo z niewypełnionymi polami. Taka strona pokazuje
     * dopisek o wersji roboczej, nie trafia do wyszukiwarek i blokuje wdrożenie produkcyjne (php artisan legal:check).
     */
    public function isDraft(): bool
    {
        return $this->needs_review || $this->placeholders() !== [];
    }

    /** @return array{html: string, toc: array<int, array{id: string, title: string}>} */
    public function content(): array
    {
        return LegalContent::render($this->body);
    }
}

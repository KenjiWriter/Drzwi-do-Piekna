<?php

namespace App\Support;

use App\Models\Photo;

/** Wybór zdjęcia dla strony: z biblioteki albo z manifestu generowanego przez `npm run images` (zdjęcia demonstracyjne). */
class Images
{
    /**
     * Zdjęcie z biblioteki (obiekt dla komponentu Picture) albo, gdy go nie wybrano, klucz zdjęcia
     * demonstracyjnego z manifestu. Dzięki temu strona działa także przed wgraniem własnych zdjęć.
     *
     * @return array<string, mixed>|string|null
     */
    public static function resolve(?Photo $photo, ?string $legacyKey = null): array|string|null
    {
        return $photo?->toImage() ?? $legacyKey;
    }
}

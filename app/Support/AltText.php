<?php

namespace App\Support;

use Anthropic\Client;
use Anthropic\Core\Exceptions\AnthropicException;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\PermissionDeniedException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Exceptions\AltTextUnavailable;
use App\Exceptions\InvalidPhoto;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Client\ClientInterface;

/**
 * Opis alternatywny zdjęcia (atrybut alt, WCAG 2.1, kryterium 1.1.1) proponowany przez Claude (Anthropic API).
 *
 * Działa tylko z kluczem `ANTHROPIC_API_KEY`; bez niego panel wymaga opisu wpisanego ręcznie. Do API trafia
 * pomniejszona kopia zdjęcia bez metadanych ({@see PhotoStore::preview()}), nic poza nią. Opis zawsze można poprawić w panelu.
 */
class AltText
{
    /** Tyle znaków mieści kolumna `photos.alt`; opis ma być krótszy, ale to twardy limit. */
    public const MAX_LENGTH = 255;

    private const SYSTEM = <<<'PROMPT'
        Piszesz teksty alternatywne (atrybut alt) do zdjęć na stronie gabinetu medycyny estetycznej w Łodzi.
        Czyta je osoba niewidoma z czytnikiem ekranu, więc opis ma oddać to, co na zdjęciu ważne: kto lub co jest
        w kadrze, jaki to kadr (np. zbliżenie, wnętrze), istotne przedmioty, nastrój światła.

        Zasady:
        - Odpowiedz wyłącznie samym opisem: jedno zdanie po polsku, najwyżej 120 znaków, bez cudzysłowów.
        - Nie zaczynaj od „Zdjęcie przedstawia”, „Na zdjęciu widać” ani podobnych wstępów.
        - Nie zgaduj tożsamości, imion, wieku ani pochodzenia osób. Nie podawaj nazw marek ani leków.
        - Nie oceniaj urody i nie obiecuj efektów zabiegów (bez słów takich jak „idealna”, „odmłodzona”, „perfekcyjna”).
        - Jeśli na zdjęciu jest czytelny napis, który coś znaczy, przytocz go krótko.
        PROMPT;

    /** `$transporter` podstawiają tylko testy (klient HTTP zamiast prawdziwego połączenia z API). */
    public function __construct(
        private readonly PhotoStore $photos,
        private readonly ?ClientInterface $transporter = null,
    ) {}

    /** Czy w tym środowisku jest klucz do Anthropic API. */
    public static function enabled(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    /**
     * Proponuje opis zdjęcia z pliku (JPEG, PNG lub WebP).
     *
     * @throws AltTextUnavailable gdy nie ma klucza, plik nie jest zdjęciem albo usługa nie odpowiedziała opisem
     */
    public function describe(string $file): string
    {
        if (! self::enabled()) {
            throw new AltTextUnavailable('Opisy z AI są wyłączone: brak klucza ANTHROPIC_API_KEY.');
        }

        try {
            $image = $this->photos->preview($file);
        } catch (InvalidPhoto $e) {
            throw new AltTextUnavailable($e->getMessage(), previous: $e);
        }

        try {
            // Fallback po stronie serwera: jeśli model odmówi z powodów zasad bezpieczeństwa, API samo ponawia
            // zapytanie na zalecanym modelu zastępczym w tym samym wywołaniu.
            $message = $this->client()->beta->messages->create(
                maxTokens: 2048,
                model: config('services.anthropic.alt_model'),
                system: self::SYSTEM,
                messages: [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'image', 'source' => ['type' => 'base64', 'mediaType' => 'image/webp', 'data' => base64_encode($image)]],
                        ['type' => 'text', 'text' => 'Napisz opis alternatywny tego zdjęcia.'],
                    ],
                ]],
                outputConfig: ['effort' => 'low'],
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
                requestOptions: ['maxRetries' => 2],
            );
        } catch (AuthenticationException|PermissionDeniedException $e) {
            Log::error('AltText: odrzucony klucz Anthropic API', ['error' => $e->getMessage()]);

            throw new AltTextUnavailable('Usługa AI odrzuciła klucz API. Sprawdź ANTHROPIC_API_KEY.', previous: $e);
        } catch (RateLimitException $e) {
            throw new AltTextUnavailable('Usługa AI jest chwilowo przeciążona. Spróbuj za minutę.', previous: $e);
        } catch (APIConnectionException $e) {
            throw new AltTextUnavailable('Brak połączenia z usługą AI. Spróbuj ponownie albo wpisz opis ręcznie.', previous: $e);
        } catch (AnthropicException $e) {
            Log::warning('AltText: błąd Anthropic API', ['error' => $e->getMessage()]);

            throw new AltTextUnavailable('Usługa AI nie odpowiedziała. Spróbuj ponownie albo wpisz opis ręcznie.', previous: $e);
        }

        if ($message->stopReason === 'refusal') {
            throw new AltTextUnavailable('Usługa AI nie opisała tego zdjęcia. Wpisz opis ręcznie.');
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        $alt = self::clean($text);

        if ($alt === '') {
            throw new AltTextUnavailable('Usługa AI zwróciła pusty opis. Spróbuj ponownie albo wpisz opis ręcznie.');
        }

        return $alt;
    }

    /** Jedno zdanie bez cudzysłowów, wstępów i nadmiarowych spacji, przycięte do długości kolumny. */
    public static function clean(string $text): string
    {
        $alt = Str::of($text)
            ->squish()
            ->replaceMatches('/^["\'„”«»“\s]+|["\'„”«»“\s]+$/u', '')
            ->replaceMatches('/^(zdjęcie|fotografia|obraz)\s+(przedstawia|pokazuje)\s*:?\s*/iu', '')
            ->replaceMatches('/^na\s+(zdjęciu|fotografii)\s+(widać|widzimy)\s*:?\s*/iu', '')
            ->toString();

        $alt = Str::ucfirst($alt);

        return Str::length($alt) > self::MAX_LENGTH ? rtrim(Str::substr($alt, 0, self::MAX_LENGTH - 1)).'…' : $alt;
    }

    private function client(): Client
    {
        return new Client(
            apiKey: config('services.anthropic.key'),
            requestOptions: $this->transporter ? ['transporter' => $this->transporter] : null,
        );
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

/**
 * Sprawdza, czy dokumenty prawne są gotowe do publikacji. Skrypt wdrożenia (deploy/deploy.sh) uruchamia to przed
 * wdrożeniem produkcyjnym: formularz zbiera dane osobowe, więc strona z wersją roboczą polityki prywatności
 * albo ze zgodą z polem „do zatwierdzenia" nie powinna trafić do ludzi.
 */
class CheckLegalDocuments extends Command
{
    protected $signature = 'legal:check';

    protected $description = 'Sprawdza, czy polityka prywatności i zgoda w formularzu są gotowe do publikacji';

    public function handle(): int
    {
        if (! Schema::hasTable('pages')) {
            $this->warn('Brak tabeli pages (migracje jeszcze nie przeszły), pomijam sprawdzenie.');

            return self::SUCCESS;
        }

        $problems = [];
        $privacy = Page::where('slug', Page::PRIVACY)->first();

        if (! $privacy) {
            $problems[] = 'Brak strony polityki prywatności. Uruchom: php artisan db:seed --class=PageSeeder (bezpieczne, nie nadpisuje istniejących).';
        } else {
            if ($privacy->needs_review) {
                $problems[] = 'Polityka prywatności jest oznaczona jako wersja robocza (panel: Treści, Dokumenty).';
            }

            foreach ($privacy->placeholders() as $placeholder) {
                $problems[] = "Polityka prywatności: pole do uzupełnienia {$placeholder}";
            }

            if (! $privacy->needs_review && ! $privacy->effective_from) {
                $problems[] = 'Polityka prywatności nie ma daty wejścia w życie.';
            }
        }

        // Zgoda w formularzu leży w lang/pl/pages.php i też bywa „do zatwierdzenia".
        foreach (Arr::flatten((array) trans('pages.contact.form.consent')) as $text) {
            foreach (Page::placeholdersIn($text) as $placeholder) {
                $problems[] = "Zgoda w formularzu kontaktowym (lang/pl/pages.php): pole do uzupełnienia {$placeholder}";
            }
        }

        if ($problems === []) {
            $this->info('Dokumenty prawne są gotowe do publikacji.');

            return self::SUCCESS;
        }

        $this->error('Dokumenty prawne nie są gotowe do publikacji:');

        foreach ($problems as $problem) {
            $this->line('  - '.$problem);
        }

        return self::FAILURE;
    }
}

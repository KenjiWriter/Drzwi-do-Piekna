<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Support\Markdown;
use Illuminate\Database\Seeder;

/**
 * Szkielet polityki prywatności i plików cookies. To nie jest tekst prawny: fakty o działaniu strony
 * (jakie dane zbiera formularz, jakie pliki cookies ustawia, czego nie robi) są wypełnione na podstawie kodu,
 * a wszystko, co wymaga decyzji prawnej, jest polem w nawiasach kwadratowych do uzupełnienia przez prawnika klienta.
 * Dopóki w treści są takie pola albo dokument jest oznaczony jako wersja robocza, strona pokazuje dopisek
 * „Wersja robocza”, ma noindex, a wdrożenie produkcyjne jest blokowane (php artisan legal:check).
 *
 * Powstaje raz (po slugu), więc ponowne uruchomienie nie nadpisuje treści zmienionej w panelu.
 * Test tests/Feature/LegalPageTest.php pilnuje, że nazwy i czasy cookies w tekście zgadzają się z konfiguracją.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        Page::firstOrCreate(['slug' => Page::PRIVACY], [
            'title' => 'Polityka prywatności i plików cookies',
            'body' => Markdown::toHtml($this->privacyPolicy()),
            'needs_review' => true,
        ]);
    }

    private function privacyPolicy(): string
    {
        return <<<'MD'
Dbamy o prywatność osób, które odwiedzają naszą stronę i korzystają z formularza kontaktowego. Poniżej wyjaśniamy, jakie dane przetwarzamy, w jakim celu, komu je przekazujemy i jakie masz prawa. Dokument opisuje też pliki cookies.

## Kto jest administratorem Twoich danych

Administratorem Twoich danych osobowych jest [pełna nazwa podmiotu prowadzącego gabinet i forma prawna], z siedzibą pod adresem [adres siedziby], NIP [NIP], REGON [REGON] (dalej: Administrator).

Gabinet Drzwi do Piękna znajduje się przy ul. Karolewska 29/31, 90-560 Łódź. W sprawach dotyczących danych osobowych możesz napisać na adres gabinet@drzwidopiekna.pl albo zadzwonić pod numer 797 745 224.

[Inspektor ochrony danych: wskazać, czy został wyznaczony, i podać jego dane kontaktowe, albo usunąć to zdanie.]

## Jakie dane przetwarzamy i w jakim celu

| Sytuacja | Dane | Cel | Podstawa prawna |
| --- | --- | --- | --- |
| Formularz kontaktowy na stronie | imię, numer telefonu, adres e-mail (podanie go jest dobrowolne), wybrany obszar zabiegu, treść wiadomości, data i godzina udzielenia zgody | odpowiedź na zapytanie i umówienie konsultacji | [podstawa prawna do zatwierdzenia przez prawnika, np. art. 6 ust. 1 lit. a RODO (zgoda)] |
| Kontakt telefoniczny lub e-mailowy | dane, które podasz w rozmowie lub wiadomości | obsługa zapytania | [podstawa prawna do zatwierdzenia przez prawnika] |
| Działanie i bezpieczeństwo strony | adres IP, data i godzina wejścia, adres odwiedzanej strony, dane przeglądarki (zapisywane w logach serwera) | zapewnienie działania strony, ochrona przed nadużyciami i spamem | [podstawa prawna do zatwierdzenia przez prawnika, np. art. 6 ust. 1 lit. f RODO (uzasadniony interes Administratora)] |

W formularzu prosimy **nie podawać informacji o stanie zdrowia**, chorobach, przyjmowanych lekach ani szczegółów medycznych. O sprawach medycznych porozmawiamy podczas konsultacji w gabinecie. [Sformułowanie do zatwierdzenia przez prawnika.]

## Co dzieje się z danymi z formularza

- Zapytanie zapisuje się w bazie danych naszego systemu, na serwerze [nazwa dostawcy hostingu i kraj lokalizacji serwera].
- Na adres e-mail gabinetu wysyłamy powiadomienie o nowym zapytaniu, z danymi wpisanymi w formularzu.
- Jeśli podasz adres e-mail, wyślemy na niego automatyczne potwierdzenie przyjęcia zapytania. Nie zawiera ono numeru telefonu ani treści Twojej wiadomości, a do jednego adresu wysyłamy najwyżej dwa takie potwierdzenia na godzinę.
- Nie zapisujemy adresu IP razem z zapytaniem.
- Chronimy formularz przed spamem: ograniczamy liczbę zapytań wysyłanych z jednego adresu IP do pięciu na minutę. Licznik prób przechowujemy w pamięci podręcznej serwera przez minutę.
- Nie podejmujemy wobec Ciebie zautomatyzowanych decyzji i nie tworzymy profili.

## Komu przekazujemy dane

Dane mogą trafić do podmiotów, które pomagają nam prowadzić stronę i obsługiwać zapytania:

- [dostawca hostingu i serwera: nazwa],
- [dostawca poczty e-mail używanej do powiadomień: nazwa],
- podmioty uprawnione do dostępu do danych na podstawie przepisów prawa.

Rezerwacja terminu online odbywa się w serwisie Moment.pl, do którego prowadzą przyciski „Umów wizytę”. Dane podane podczas rezerwacji przetwarza operator tego serwisu, na zasadach z jego polityki prywatności. [Do zatwierdzenia: rola Moment.pl (odrębny administrator czy podmiot przetwarzający).]

[Informacja o przekazywaniu danych poza Europejski Obszar Gospodarczy: wpisać „nie przekazujemy” albo opisać zabezpieczenia.]

## Jak długo przechowujemy dane

- Zapytania z formularza: [okres przechowywania, np. 12 miesięcy od ostatniego kontaktu], a potem je usuwamy.
- Jeśli zapytanie zakończy się wizytą, dane pacjenta przetwarzamy dalej na zasadach dotyczących dokumentacji medycznej, opisanych w odrębnej informacji dla pacjentów. [Do uzupełnienia.]
- Logi aplikacji: 14 dni. Logi serwera WWW: [okres].
- Kopie zapasowe bazy danych: 30 dni.

## Twoje prawa

Masz prawo do:

- dostępu do swoich danych i uzyskania ich kopii,
- sprostowania danych,
- usunięcia danych,
- ograniczenia przetwarzania,
- przenoszenia danych,
- sprzeciwu wobec przetwarzania, jeśli podstawą jest uzasadniony interes Administratora,
- cofnięcia zgody w dowolnym momencie, bez wpływu na zgodność z prawem przetwarzania, którego dokonano przed jej cofnięciem.

Aby skorzystać z tych praw, napisz na gabinet@drzwidopiekna.pl albo zadzwoń pod numer 797 745 224. [Sposób potwierdzania tożsamości i termin odpowiedzi do zatwierdzenia przez prawnika.]

## Skarga do organu nadzorczego

Jeśli uważasz, że przetwarzamy dane niezgodnie z prawem, możesz złożyć skargę do Prezesa Urzędu Ochrony Danych Osobowych (ul. Stawki 2, 00-193 Warszawa).

## Czy musisz podać dane

Podanie imienia i numeru telefonu jest dobrowolne, ale potrzebne, żebyśmy mogli oddzwonić i odpowiedzieć na zapytanie. Adres e-mail, obszar zabiegu i treść wiadomości są opcjonalne. Zgodę możesz cofnąć w każdej chwili.

## Cookies i podobne technologie

Pliki cookies to małe pliki zapisywane w Twojej przeglądarce. Nasza strona korzysta wyłącznie z plików niezbędnych do jej działania i do bezpieczeństwa formularza kontaktowego. **Nie używamy plików analitycznych, reklamowych ani śledzących** i nie osadzamy zewnętrznych narzędzi (czcionki są przechowywane na naszym serwerze), dlatego nie wyświetlamy okna z prośbą o zgodę na cookies.

### Pliki, które zapisujemy

| Nazwa | Do czego służy | Ważność | Rodzaj |
| --- | --- | --- | --- |
| `drzwi-do-piekna-session` | identyfikuje sesję w przeglądarce, dzięki czemu formularz kontaktowy działa bezpiecznie, a po wysłaniu możemy wyświetlić potwierdzenie | 2 godziny | własny, techniczny, niezbędny |
| `XSRF-TOKEN` | zabezpiecza formularz przed podrobieniem żądania (atak CSRF) | 2 godziny | własny, techniczny, niezbędny |

Strona zapisuje też w pamięci sesji przeglądarki (sessionStorage) wpis `ddp-intro`, który zapamiętuje, że animacja powitalna została już pokazana. Nie zawiera on danych osobowych i znika po zamknięciu karty. Pracownicy gabinetu, którzy logują się do panelu redakcyjnego, korzystają dodatkowo z plików sesji panelu. Dotyczy to wyłącznie zalogowanych pracowników.

### Jak zarządzać plikami cookies

Pliki cookies możesz usunąć lub zablokować w ustawieniach przeglądarki. Zablokowanie plików niezbędnych może uniemożliwić wysłanie formularza kontaktowego.

Jeśli w przyszłości dodamy narzędzia analityczne lub marketingowe, poprosimy Cię o zgodę, zanim zaczną działać, i zaktualizujemy ten dokument.

## Linki do innych serwisów

Na stronie znajdują się odnośniki do serwisów zewnętrznych: Moment.pl (rezerwacja), Google (wizytówka i mapa), Instagram, Facebook i ZnanyLekarz. Po przejściu do nich obowiązują ich własne polityki prywatności, za które nie odpowiadamy.

## Zmiany tego dokumentu

Aktualną wersję zawsze publikujemy na tej stronie, a datę wejścia w życie podajemy u góry. [Sposób informowania o istotnych zmianach do zatwierdzenia przez prawnika.]
MD;
    }
}

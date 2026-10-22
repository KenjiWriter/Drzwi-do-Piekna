<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Support\Markdown;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Cztery artykuły ze starej strony drzwidopiekna.pl (/blog/...). Adresy, autorzy i daty są oryginalne,
 * więc stare linki przekierowują 1:1. Treść jest przeniesiona z poprawkami zgodnymi z zasadami projektu
 * (CLAUDE.md i docs/PLAN.md, "Prawo i prywatność"):
 *
 * - usunięta nazwa substancji wydawanej na receptę (artykuł 1),
 * - złagodzone obietnice i superlatywy ("gwarantującego", "idealna", "doskonale", "maksymalnie"),
 * - artykuł 1: usunięte porównanie, że tylko lekarze znają anatomię, a kosmetolodzy nie,
 * - poprawione literówki (osteoporozy, istotną rolę), uporządkowane punktory i źródła,
 * - linki do zabiegów prowadzą na nowe adresy,
 * - bez zdjęć: stare były stockowe, cudze (CBS News) albo przedstawiały prywatną osobę, więc zdjęcia
 *   trzeba dodać w panelu, a opisy "zdjęcie poniżej" przepisano na tekst.
 *
 * Wpisy powstają raz (po slugu), więc ponowne uruchomienie seedera nie nadpisuje zmian z panelu.
 * Wymagają przeglądu przez klientkę i prawnika przed startem, patrz docs/PLAN.md.
 */
class PostSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->posts() as $slug => $post) {
            Post::firstOrCreate(['slug' => $slug], [
                'is_published' => true,
                ...$post,
                // Źródło artykułów jest w Markdown (czytelne w seederze), a w bazie ląduje HTML dla edytora wizualnego.
                'body' => Markdown::toHtml($post['body']),
                'published_at' => $this->date($post['published_at']),
                'created_at' => $this->date($post['published_at']),
                'updated_at' => $this->date($post['updated_at']),
            ]);
        }
    }

    /** Daty ze starej strony są w UTC, a baza trzyma czas w strefie aplikacji. */
    private function date(string $utc): Carbon
    {
        return Carbon::parse($utc, 'UTC')->setTimezone(config('app.timezone'));
    }

    /** @return array<string, array<string, mixed>> */
    private function posts(): array
    {
        $doctor = ['author_name' => 'dr Anita Symonowicz', 'author_role' => 'Lekarz medycyny estetycznej, Drzwi do Piękna'];
        $cosmetologist = ['author_name' => 'Katarzyna Klepacz', 'author_role' => 'Kosmetolog, Drzwi do Piękna'];

        return [
            'bezpieczne-czy-nie-sprawdz-w-czyje-rece-sie-oddajesz' => [
                ...$doctor,
                'title' => 'Bezpieczne czy nie? Sprawdź, w czyje ręce się oddajesz',
                'excerpt' => 'Zabiegi kosmetyczne i zabiegi medycyny estetycznej to nie to samo. Sprawdź, na co zwrócić uwagę, wybierając gabinet, i dlaczego kwalifikacje osoby wykonującej zabieg mają znaczenie.',
                'published_at' => '2018-08-25 22:23:54',
                'updated_at' => '2020-11-27 18:05:20',
                'body' => <<<'MD'
W ostatnich latach powstało bardzo wiele miejsc, w których można skorzystać z różnych zabiegów, zarówno kosmetycznych, jak i medycyny estetycznej. Jeśli decydujesz się na zabieg, rozróżnienie tych dwóch rzeczy może okazać się kluczowe dla Twojego bezpieczeństwa i efektu zabiegu.

Na szczęście coraz więcej osób świadomie poszukuje gabinetów medycznych, w których zabiegami z użyciem leków i wypełniaczy zajmują się dobrze wyszkoleni lekarze. Dlaczego to takie ważne? Ponieważ zabieg z przerwaniem ciągłości tkanek trzeba przeprowadzać w odpowiednio przystosowanym do tego pomieszczeniu, używając przebadanych i certyfikowanych urządzeń oraz materiałów.

Każdy lekarz lub lekarz dentysta, który proponuje zabiegi medycyny estetycznej, powinien mieć ważne prawo wykonywania zawodu oraz ukończone odpowiednie szkolenia lub studia podyplomowe. Lekarze szczegółowo znają anatomię twarzy, przebieg naczyń krwionośnych i nerwów. Wiedzą też, jak postępować w sytuacji ewentualnych powikłań, np. reakcji alergicznej, która może wymagać podania leku dostępnego wyłącznie na receptę. Dlatego warto zainteresować się zawczasu, komu powierzamy nasze zdrowie, jakie ma kwalifikacje i wykształcenie.

W gabinecie Drzwi do Piękna zabiegami medycyny estetycznej zajmują się wyłącznie lekarze z odpowiednimi kwalifikacjami i doświadczeniem. Ukończyliśmy studia podyplomowe z zakresu medycyny estetycznej na Warszawskim Uniwersytecie Medycznym i co roku bierzemy udział w kilkunastu szkoleniach.

Zabiegami pielęgnacyjnymi zajmuje się kosmetolog, który stale doskonali swoje umiejętności. Nasz fizjoterapeuta wykonuje nie tylko masaże ujędrniające i poprawiające wygląd skóry, ale jest też przygotowany, by w razie potrzeby pomóc pacjentom przy kontuzji lub nadwyrężeniu stawu czy mięśnia.

Zapraszamy do gabinetu, w którym liczą się kwalifikacje i bezpieczeństwo pacjentek i pacjentów.
MD,
            ],

            'jak-slonce-dziala-na-organizm' => [
                ...$cosmetologist,
                'title' => 'Jak słońce działa na nasz organizm?',
                'excerpt' => 'Pozytywne i negatywne działanie słońca na organizm. Dowiedz się, z jakich aspektów jego działania warto korzystać, a z jakich lepiej zrezygnować.',
                'published_at' => '2020-07-10 12:41:19',
                'updated_at' => '2020-11-27 18:05:41',
                'body' => <<<'MD'
O oddziaływaniu słońca na organizm człowieka napisano już bardzo wiele. Wciąż prowadzone są badania naukowe dotyczące jego wpływu na poszczególne komórki organizmu i skóry. Ważne jest jednak, aby mieć na uwadze, jakie skutki może mieć zbyt częste korzystanie z promieni słonecznych i jak się chronić, aby nie spowodować bezpośredniego zagrożenia dla organizmu. Pamiętajmy także, że słońce to też dużo energii i dobre samopoczucie.

## Pozytywne oddziaływanie promieni słonecznych

1. Pod wpływem słońca produkowana jest serotonina, zwana hormonem szczęścia. To dlatego w okresie jesienno-zimowym, gdy mamy jej mniej w organizmie, częściej dopadają nas stany depresyjne.
2. Drugim hormonem zależnym od światła jest melatonina, która odpowiada za regulację cyklu dobowego. Gdy robi się ciemno, jej poziom wzrasta i jest to sygnał dla mózgu, że zbliża się pora spania. Dlatego latem, gdy dłużej świeci słońce, zazwyczaj mamy więcej energii. Zimą natomiast chce nam się więcej spać.
3. Promieniowanie UV jest wykorzystywane w leczeniu wielu chorób, w tym krzywicy, łuszczycy, egzemy i żółtaczki. Leczenie odbywa się pod nadzorem lekarza.
4. W skórze pod wpływem promieniowania UV stymulowana jest produkcja witaminy D3, która jest niezbędna do funkcjonowania gospodarki wapniowo-fosforanowej. Witamina ta odgrywa istotną rolę w przebiegu wielu chorób i problemów skórnych, np. łuszczycy, chorób reumatologicznych oraz dotyczących układu nerwowego i sercowo-naczyniowego. Jej niewystarczająca ilość przyczynia się do rozwoju krzywicy u dzieci, a u dorosłych prowadzi do osteoporozy. Niedobór tej witaminy w społeczeństwie jest duży, dlatego należy ją suplementować przez cały rok, nie przekraczając dawek zaleconych przez lekarza. Wystarczy 15–20 minut dziennie, by rozpocząć proces syntezy. Ważne jest, by nie dopuścić do przegrzania organizmu i poparzeń.

Czy dużo ryzykujemy, próbując naturalnie zaspokoić zapotrzebowanie poprzez skórną syntezę? Czytajcie dalej, bo teraz przejdziemy do drugiej strony słońca, tej mrocznej.

## Negatywne oddziaływanie promieni słonecznych

Wiele z Was zapewne słyszało, że słońce niszczy skórę i należy używać kremów z filtrem, zwłaszcza w okresie letnim. I jest to jak najbardziej prawda. Ale żeby lepiej zrozumieć to działanie na skórę i procesy starzenia, zacznijmy od początku.

Słońce emituje ogromne ilości promieniowania elektromagnetycznego. Część z nich stanowi promieniowanie ultrafioletowe UVA, UVB i UVC (UVR, ang. *Ultraviolet Radiation*). Dzielą się one ze względu na długość fal następująco.

### Promieniowanie UVA (UVA1 i UVA2)

- Stanowi ok. 95% promieniowania docierającego do Ziemi.
- Jego natężenie jest stałe bez względu na porę roku.
- Przenika przez szyby i chmury.
- Dociera do skóry właściwej, powodując fotostarzenie i głębokie zmarszczki.
- Odpowiada za pojawienie się krótkotrwałej opalenizny.
- Ma zdolność pośredniego uszkadzania DNA komórek poprzez nasilenie syntezy wolnych rodników tlenowych, które niszczą struktury kolagenu i elastyny. To właśnie te włókna są odpowiedzialne za jędrność i elastyczność skóry.
- Uszkadza ściany naczyń krwionośnych.
- Nadmierne dawki UVA kumulują się w skórze, a zmiany, jakie w niej zachodzą, są widoczne po wielu latach.
- Przyczynia się do rozwoju nowotworów skóry.
- Powoduje reakcje fotoalergiczne i fototoksyczne.

Dobrze pokazuje to znany przypadek zawodowego kierowcy ciężarówki, który spędził w pojeździe blisko 28 lat. Na tej połowie jego twarzy, która znajdowała się bliżej okna, widać głębokie zmarszczki i znacznie grubszą skórę ([źródło: CBS News](https://www.cbsnews.com/news/trucker-accumulates-skin-damage-on-left-side-of-his-face-after-28-years-on-the-road/)). Promieniowanie UVA ma zdolność przenikania przez szyby okienne, penetruje naskórek i skórę właściwą. Dlatego nawet jadąc samochodem, warto posmarować odsłonięte części ciała, nie zapominając o dłoniach (trzymamy je na kierownicy w sposób, który ułatwia penetrację promieni).

### Promieniowanie UVB

- Stanowi 5% promieniowania docierającego do Ziemi.
- W naszej szerokości geograficznej jego nasilenie jest największe latem.
- Jego intensywność jest największa w godzinach 10–15.
- Dociera do poziomu naskórka.
- Powoduje rumień, oparzenia, długotrwałą pigmentację i nowotwory skóry.
- Obniża odporność organizmu (może spowodować wystąpienie opryszczki po kąpieli słonecznej).

Przykład z praktyki to rumień po nadmiernej ekspozycji na promienie UVB. Osoba w połowie czerwca spacerowała po plaży, a temperatura wynosiła ok. 20 stopni C. Przez dosyć mocny wiatr miała złudne wrażenie, że słońce „nie grzeje”. Dopiero po ok. godzinie posmarowała się kremem z filtrem SPF 20. Efektem było poparzone ucho. Tak, uszy też smarujemy kremem z filtrem!

### Promieniowanie UVC

- Jest najmocniejsze, uszkadza łańcuchy DNA i prowadzi do ich mutacji.
- Jest zatrzymywane przez atmosferę i nie dociera do powierzchni Ziemi.
- Wykorzystuje się je w specjalnych lampach do dezynfekcji i sterylizacji pomieszczeń ze względu na zdolność zabijania organizmów żywych.

## Podsumowanie

Każdego dnia promieniowanie słoneczne ma wpływ na naszą skórę i organizm. Umiejętne korzystanie ze słońca w leczeniu niektórych problemów skórnych może przynieść pozytywne skutki, ale jego nadmierne działanie poważnie szkodzi. Jak podaje WHO: „There is no such thing as a healthy tan!”, czyli nie ma czegoś takiego jak zdrowa opalenizna.

Oczywiście korzystanie z filtrów przeciwsłonecznych jest wolnym wyborem każdego, jednak razem z lekarzami z gabinetu Drzwi do Piękna, jako osoby zajmujące się pielęgnacją skóry, zachęcamy do ochrony przeciwsłonecznej przez cały rok. Pamiętajcie także o prawidłowej suplementacji witaminy D3 oraz o zabezpieczeniu najbliższych, w tym dzieci, które są najbardziej narażone ze względu na bardzo delikatną skórę.

Więcej o tym, jak dobrać krem z filtrem, w kolejnym wpisie: [Filtry przeciwsłoneczne](/blog/filtry-przeciwsloneczne).

## Literatura

1. [Photoprotection. Recommendations of the Polish Dermatological Society. Part 1: Ultraviolet radiation and sunscreens](https://pdfs.semanticscholar.org/2e81/918671d1b02ee22a12e90a149494c7ce31b1.pdf). J. Narbutt, H. Wolska, A. Kaszuba i in.
2. A. Kołodziejczak, *Kosmetologia*, tom I, s. 172–174.
3. [Dokument WHO (PDF)](https://apps.who.int/iris/bitstream/handle/10665/69771/WHO_SDE_PHE_06.01_eng.pdf)
4. [WHO: The known health effects of UV](https://www.who.int/news-room/q-a-detail/the-known-health-effects-of-uv)
MD,
            ],

            'pielegnacja-posloneczna' => [
                ...$cosmetologist,
                'title' => 'Pielęgnacja posłoneczna',
                'excerpt' => 'Jak powinna wyglądać pielęgnacja posłoneczna? Na co zwrócić uwagę, jakich składników szukać w kosmetykach i jakimi zabiegami w gabinecie ją uzupełnić.',
                'published_at' => '2020-08-26 09:20:55',
                'updated_at' => '2020-11-27 18:05:54',
                'body' => <<<'MD'
Czas wakacyjnych wyjazdów powoli dobiega końca. Zostają miłe wspomnienia i pełni sił wracamy do obowiązków. Jednak na skórze mogą pozostać niechciane pamiątki. Nasza skóra po słonej lub chlorowanej wodzie może stać się przesuszona i szorstka. Promieniowanie słoneczne może powodować przebarwienia i popękane naczynia krwionośne. W naszym gabinecie ułożymy plan zabiegowy dostosowany do indywidualnych oczekiwań i potrzeb skóry, tak aby Twoja pielęgnacja posłoneczna nabrała odpowiedniej równowagi.

Pielęgnację posłoneczną można podzielić na dwa etapy:

- wczesną, zaraz po przebywaniu na słońcu,
- późną, już po okresie wakacyjnym i korzystaniu ze słońca.

Dodatkowo możemy ją podzielić na pielęgnację domową i profesjonalne zabiegi.

## Na co zwrócić uwagę w pierwszej kolejności?

W pielęgnacji domowej szukajmy preparatów ze składnikami łagodzącymi i nawilżającymi, jak np.:

- alantoina,
- d-pantenol,
- aloes,
- masło shea,
- ceramidy,
- kwas hialuronowy,
- olej z awokado,
- witaminy A, C, E,
- beta-glukan.

Są to substancje, które jako pierwsze warto zastosować na skórę, żeby zachować odpowiednie nawilżenie i odbudować płaszcz hydrolipidowy. W późniejszym etapie można skupić się na poszczególnych „pamiątkach” w postaci przebarwień czy rumienia.

## Z których zabiegów warto skorzystać w gabinecie?

- [Infuzja tlenowa](/zabiegi/szyja-i-dekolt/dermaoxy): oprócz wprowadzenia nawilżającej ampułki, wykorzystanie czystego tlenu wspomaga usuwanie wolnych rodników i przyspiesza regenerację skóry.
- [Geneo+](/zabiegi/jakosc-skory/geneo): złuszcza martwy naskórek, a dzięki wybraniu odpowiedniego serum i kapsułki może być zastosowany do cery tłustej, wymagającej mocnego odżywienia lub z przebarwieniami.
- [Mezoterapia igłowa](/zabiegi/twarz/mezoterapia-iniekcyjna): sprawdza się, gdy skóra wymaga rewitalizacji posłonecznej.
- [Dermapen 4.0](/zabiegi/jakosc-skory/dermapen-4-0): pobudza skórę do regeneracji.
- [Peelingi chemiczne](/zabiegi/jakosc-skory/peelingi-chemiczne): sprawdzą się, gdy skóra wymaga złuszczenia, widoczne są poszerzone pory, przebarwienia czy drobne zmarszczki.

Wymienione zabiegi w późniejszej pielęgnacji profesjonalnej oczywiście modyfikujemy i dobieramy inne, tak by jak najlepiej odpowiadały potrzebom Twojej skóry.

**Pamiętajcie, że koniec wakacji nie oznacza końca używania ochrony przeciwsłonecznej. SPF stosujemy cały rok.**
MD,
            ],

            'filtry-przeciwsloneczne' => [
                ...$cosmetologist,
                'title' => 'Filtry przeciwsłoneczne',
                'excerpt' => 'Co kryje się za oznaczeniami SPF i UVA na opakowaniu kremu z filtrem i jak go prawidłowo stosować, aby chronić skórę.',
                'published_at' => '2020-08-26 10:26:00',
                'updated_at' => '2020-11-27 18:05:31',
                'body' => <<<'MD'
W poprzednim wpisie ([Jak słońce działa na nasz organizm?](/blog/jak-slonce-dziala-na-organizm)) dowiedzieliśmy się, jak działają promienie słoneczne, że w dłuższej perspektywie są one szkodliwe i że należy się przed nimi chronić. Jak to robić i czego używać, przeczytacie poniżej.

> **Pamiętaj!** Żaden produkt z filtrem nie chroni skóry w 100% przed promieniowaniem UV. W Unii Europejskiej zabronione jest umieszczanie oświadczeń typu „całkowita ochrona” czy „bloker”. Jakaś część promieni zawsze dociera do skóry, a takie oznaczenia mogą sprawiać iluzję ochrony niemożliwej do osiągnięcia. [1]

## Jak dobrać krem z odpowiednią ochroną przeciwsłoneczną?

Aby zapewnić skuteczną ochronę, należy szukać produktów o szerokim spektrum działania, czyli chroniących przed promieniowaniem UVB i UVA.

## Co oznacza SPF na produkcie?

SPF (ang. *Sun Protection Factor*) to współczynnik ochrony przed słońcem. Jest międzynarodowym współczynnikiem odnoszącym się do ochrony przed promieniowaniem UVB. Na opakowaniu najczęściej widnieje SPF 30, SPF 50 lub SPF 50+. Jest to stosunek minimalnej dawki promieniowania wywołującej rumień na skórze chronionej kosmetykiem z filtrem do dawki wywołującej rumień na skórze niechronionej.

### Co oznacza SPF 30?

SPF 30 oznacza, że dawka promieniowania wywołująca rumień po zastosowaniu danego preparatu może być 30-krotnie wyższa niż dawka potrzebna do wywołania rumienia na skórze niechronionej. Nie odnosi się to do czasu ekspozycji na promieniowanie, ale do dawki promieniowania.

### Jak się go oblicza?

SPF = MED skóry chronionej / MED skóry niechronionej, gdzie MED to minimalna dawka rumieniowa (ang. *Minimal Erythema Dose*).

| Klasa ochrony | Wskaźnik ochrony przeciwsłonecznej SPF |
| --- | --- |
| Niska | 6, 10 |
| Średnia | 15, 20, 25 |
| Wysoka | 30, 50 |
| Bardzo wysoka | 50+ |

*Podział produktów kosmetycznych chroniących przed promieniowaniem UV zgodnie z wytycznymi Komisji UE [3]*

Im wyższa ochrona, tym różnica w ochronie jest mniejsza. Wiadomo, że:

- krem o współczynniku SPF 15 chroni skórę przed promieniowaniem UVB w 93,3% (czyli przepuści 1/15 promieniowania),
- krem o SPF 30 w 96,7% (przepuści 1/30 promieniowania),
- krem o SPF 50 w 98,3% (przepuści 1/50). [2]

## Jak sprawdzić, czy kosmetyk ma ochronę UVA?

Wskaźnik ochrony przed UVA oznacza się jako PPD (ang. *Persistent Pigment Darkening*). Oznacza stosunek minimalnej dawki UVA potrzebnej do spowodowania trwałej pigmentacji na skórze chronionej produktem do minimalnej dawki UVA potrzebnej do spowodowania najmniejszego efektu pigmentacji na tej samej skórze niechronionej.

Symbol UVA w kółeczku oznacza, że produkt spełnia zalecenia Komisji Europejskiej w zakresie skuteczności ochrony przed promieniowaniem UVA. Zgodnie z tymi zaleceniami stopień ochrony przed UVA powinien stanowić co najmniej 1/3 wartości współczynnika ochrony przed UVB. [1]

Przykładowo: mamy krem z SPF 30 (ochrona przed UVB), więc 1/3 tej wartości musi chronić przed UVA. Oblicza się to następująco:

- SPF 30 × 1/3 = PPD 10
- SPF 50 × 1/3 = PPD 16
- SPF 50+ × 1/3 = PPD 20

## W takim razie czego szukać na opakowaniu?

SPF 50, SPF 50+, UVA w kółeczku, napis „Broad Spectrum”, „wysoka ochrona”. Nie muszą występować wszystkie naraz. Najczęściej spotkamy SPF 50 i UVA w kółeczku.

## Jakie filtry są stosowane w kremach?

Filtry UV mogą być organiczne (chemiczne) lub nieorganiczne (fizyczne, mineralne).

- Filtry chemiczne pochłaniają promieniowanie (aminobenzoesany, benzofenony, salicylany, cynamoniany).
- Filtry fizyczne odbijają i rozpraszają promieniowanie (tlenek cynku i dwutlenek tytanu).

Nowoczesne kosmetyki przeciwsłoneczne zawierają różne kombinacje filtrów fizycznych i chemicznych, które dzięki synergistycznemu działaniu zapewniają efektywną ochronę przed promieniowaniem UV.

## Ile kremu należy nałożyć?

Prawidłowa ilość preparatu to 2 mg na cm² powierzchni skóry. Ile to w praktyce? Pomocna jest „reguła dwóch palców”: jedna porcja to krem wyciśnięty na dwa palce dłoni. Opisuje ją m.in. [dermoteka.com.pl](http://dermoteka.com.pl/2019/06/11/filtry-przeciwsloneczne/).

## Co jaki czas nakładać filtr?

Według WHO należy reaplikować krem z filtrem co dwie godziny oraz po każdej kąpieli i wysiłku fizycznym.

## Źródła

1. Zalecenia Komisji z dnia 22 września 2006 r. w sprawie skuteczności produktów ochrony przeciwsłonecznej i odnoszących się do nich oświadczeń (C(2006) 4089).
2. [Photoprotection. Recommendations of the Polish Dermatological Society. Part 1: Ultraviolet radiation and sunscreens](https://pdfs.semanticscholar.org/2e81/918671d1b02ee22a12e90a149494c7ce31b1.pdf).
3. [Komisja Europejska, komunikat prasowy IP/09/1057](https://ec.europa.eu/commission/presscorner/detail/pl/IP_09_1057).
MD,
            ],
        ];
    }
}

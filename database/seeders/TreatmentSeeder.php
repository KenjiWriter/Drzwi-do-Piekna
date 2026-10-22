<?php

namespace Database\Seeders;

use App\Models\Treatment;
use App\Models\TreatmentCategory;
use Illuminate\Database\Seeder;

/**
 * Zabiegi i obszary na podstawie obecnej strony klienta (drzwidopiekna.pl).
 *
 * Do potwierdzenia z klientką: przypisanie zabiegów do obszarów (na starej stronie jeden zabieg
 * bywał w kilku kategoriach) oraz opisy. Tylko ClearLift, kwas hialuronowy i Geneo mają pełne opisy
 * ze starej strony. Nazwy handlowe leków na receptę (toksyna botulinowa) są celowo pominięte.
 */
class TreatmentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->data() as $i => $category) {
            $treatments = $category['treatments'];
            unset($category['treatments']);

            $record = TreatmentCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['sort_order' => $i + 1],
            );

            foreach ($treatments as $j => $treatment) {
                Treatment::updateOrCreate(
                    ['slug' => $treatment['slug']],
                    $treatment + ['treatment_category_id' => $record->id, 'sort_order' => $j + 1],
                );
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function data(): array
    {
        return [
            [
                'slug' => 'twarz',
                'name' => 'Twarz',
                'summary' => 'Modelowanie, konturowanie, poprawa jakości skóry.',
                'intro' => 'Zabiegi, które poprawiają kondycję skóry twarzy i subtelnie modelują rysy, tak aby efekt był naturalny i zgodny z Twoją urodą.',
                'image_key' => 'tile-face',
                'treatments' => [
                    [
                        'slug' => 'clearlift',
                        'legacy_slug' => 'clearlift-laserowe-odmladzanie',
                        'name' => 'ClearLift, laserowe odmładzanie',
                        'summary' => 'Laserowe odmładzanie z natychmiastowym napięciem skóry i bez rekonwalescencji.',
                        'description' => "Zabieg poprawia kondycję skóry, natychmiast ją napina, wygładza drobne zmarszczki i rozświetla. Wykorzystuje laser Q-Switch w technice frakcyjnej. Nie wymaga rekonwalescencji, więc zaraz po zabiegu możesz wrócić do codziennych zajęć.\n\nZabieg nie wymaga znieczulenia. Laser przenika przez naskórek, nie usuwając go, i wywołuje mikrouszkodzenia w głębszych warstwach skóry. Regeneracja przebiega szybko i nie zostają widoczne rany, a Ty odczuwasz łagodne mrowienie. Zabieg jest bezpieczny dla wszystkich fototypów skóry i obszarów ciała.",
                        'indications' => [
                            'Drobne zmarszczki i objawy fotostarzenia',
                            'Utrata elastyczności i jędrności skóry twarzy, szyi, dekoltu, dłoni, ramion i kolan',
                            'Przebarwienia (słoneczne, pozapalne, hormonalne)',
                            'Rozszerzone pory',
                        ],
                        'effects' => [
                            'Pierwsze efekty po kilkunastu dniach, pełna poprawa po 2–3 miesiącach',
                            'Rozświetlenie skóry i rozjaśnienie przebarwień',
                            'Spłycenie zmarszczek',
                            'Lepsze napięcie i kondycja skóry, zwężenie porów',
                        ],
                        'duration' => 'Od kilkunastu minut do godziny, zależnie od obszaru',
                    ],
                    [
                        'slug' => 'wypelniacze-kwas-hialuronowy',
                        'legacy_slug' => 'korekta-zmarszczek-i-wolumetria-twarzy',
                        'name' => 'Korekta zmarszczek i wolumetria twarzy kwasem hialuronowym',
                        'summary' => 'Wypełniacze z kwasem hialuronowym spłycają zmarszczki i przywracają objętość twarzy.',
                        'description' => "Wypełniacze na bazie kwasu hialuronowego spłycają zmarszczki mimiczne i przywracają objętość twarzy, którą z wiekiem tracimy. Czas utrzymywania się efektu zależy od rodzaju preparatu, miejsca podania i ilości.\n\nRodzaj i ilość preparatu dobieramy wspólnie podczas konsultacji, tak aby rysy pozostały naturalne.",
                        'indications' => [
                            'Spłycenie lub likwidacja bruzd nosowo-wargowych',
                            'Spłycenie lub likwidacja linii marionetki',
                            'Wypełnianie policzków',
                            'Wypełnianie doliny łez (efekt zmęczonej twarzy)',
                            'Modelowanie skroni i brody (wolumetria)',
                        ],
                        'effects' => [
                            'Przywrócenie utraconej objętości twarzy',
                            'Odmłodzenie wyglądu',
                            'Efekt utrzymuje się od 6 do 12 miesięcy',
                        ],
                    ],
                    [
                        'slug' => 'zmarszczki-mimiczne',
                        'legacy_slug' => 'korekta-zmarszczek-mimicznych-toksyna-botulinowa-botox',
                        'name' => 'Korekta zmarszczek mimicznych',
                        'summary' => 'Wygładzenie zmarszczek mimicznych. Efekt utrzymuje się zwykle od 3 do 6 miesięcy.',
                    ],
                    [
                        'slug' => 'profhilo',
                        'legacy_slug' => 'profhilo',
                        'name' => 'Profhilo, molekuła młodości',
                        'summary' => 'Odmładzanie i nawilżanie skóry preparatem Profhilo. Efekt utrzymuje się do roku–półtora.',
                    ],
                    [
                        'slug' => 'ipixel-er-yag',
                        'legacy_slug' => 'ipixel-eryag',
                        'name' => 'iPixel Er:YAG',
                        'summary' => 'Laser frakcyjny do odmładzania i resurfacingu skóry, także przy bliznach i nierównościach.',
                    ],
                    [
                        'slug' => 'dye-vl',
                        'legacy_slug' => 'dye-vl',
                        'name' => 'Dye-VL',
                        'summary' => 'Laser do zamykania naczynek i naczyniaków, łagodzenia rumienia oraz rozjaśniania przebarwień.',
                    ],
                    [
                        'slug' => 'mezoterapia-iniekcyjna',
                        'legacy_slug' => 'mezoterapia-iniekcyjna',
                        'name' => 'Mezoterapia iniekcyjna',
                        'summary' => 'Wprowadzenie preparatów odżywczych i nawilżających w głąb skóry. Skład dobieramy podczas konsultacji.',
                    ],
                    [
                        'slug' => 'osocze-bogatoplytkowe',
                        'legacy_slug' => 'osocze-bogatoplytkowe-wampirzy-lifting',
                        'name' => 'Osocze bogatopłytkowe, wampirzy lifting',
                        'summary' => 'Mezoterapia osoczem pobranym od Ciebie, które wspiera regenerację i poprawia jakość skóry.',
                    ],
                    [
                        'slug' => 'retix-all-eye-therapy',
                        'legacy_slug' => 'retix-all-eye-therapy',
                        'name' => 'Retix All Eye Therapy',
                        'summary' => 'Pielęgnacja okolic oczu: przebarwienia, obrzęki, drobne zmarszczki i nawilżenie.',
                    ],
                    [
                        'slug' => 'relax-refresh',
                        'legacy_slug' => 'relax-refresh',
                        'name' => 'Relax & Refresh',
                        'summary' => 'Dwuetapowy zabieg dla mężczyzn: odświeżenie skóry i rozluźnienie mimicznych mięśni.',
                    ],
                ],
            ],
            [
                'slug' => 'usta',
                'name' => 'Usta',
                'summary' => 'Naturalne modelowanie i harmonijny kształt.',
                'intro' => 'Delikatna korekta kształtu i nawilżenie ust, z poszanowaniem proporcji całej twarzy.',
                'image_key' => 'tile-lips',
                'treatments' => [
                    [
                        'slug' => 'modelowanie-ust',
                        'legacy_slug' => null,
                        'name' => 'Modelowanie i nawilżenie ust',
                        'summary' => 'Wypełnianie i nawilżanie ust kwasem hialuronowym dla naturalnego, harmonijnego kształtu.',
                        'description' => "Usta modelujemy kwasem hialuronowym, dbając o proporcje całej twarzy. Możesz wybrać subtelne nawilżenie albo delikatną korektę kształtu i objętości.\n\nRodzaj i ilość preparatu dobieramy podczas konsultacji. Efekt ma być naturalny i zgodny z Twoimi oczekiwaniami.",
                    ],
                ],
            ],
            [
                'slug' => 'szyja-i-dekolt',
                'name' => 'Szyja i dekolt',
                'summary' => 'Ujędrnianie, poprawa struktury skóry, regeneracja.',
                'intro' => 'Skóra szyi i dekoltu starzeje się szybciej niż twarz. Te zabiegi poprawiają jej strukturę, napięcie i koloryt.',
                'image_key' => 'tile-neck',
                'treatments' => [
                    [
                        'slug' => 'retibooster-therapy',
                        'legacy_slug' => 'retibooster-with-retinol-tgf-activator',
                        'name' => 'Retibooster Therapy',
                        'summary' => 'Terapia retinolowa nowej generacji z czystym retinolem 4% i serum z czynnikiem wzrostu.',
                    ],
                    [
                        'slug' => 'smart-dna',
                        'legacy_slug' => 'smart-dna',
                        'name' => 'Smart DNA',
                        'summary' => 'Silnie antyoksydacyjny zabieg całoroczny, który wspiera naprawę skóry i jej rozświetlenie.',
                    ],
                    [
                        'slug' => 'retibooster-smart-dna',
                        'legacy_slug' => 'retibooster-therapy-smart-dna-therapy',
                        'name' => 'Retibooster Therapy + Smart DNA Therapy',
                        'summary' => 'Połączenie zabiegów Retibooster i Smart DNA o działaniu depigmentującym i przeciwstarzeniowym.',
                    ],
                    [
                        'slug' => 'dermaoxy',
                        'legacy_slug' => 'dermaoxy',
                        'name' => 'DermaOxy',
                        'summary' => 'Infuzja tlenowa połączona z serum: rozświetlenie i nawilżenie skóry.',
                    ],
                ],
            ],
            [
                'slug' => 'jakosc-skory',
                'name' => 'Jakość skóry',
                'summary' => 'Nawilżenie, rozświetlenie i regeneracja skóry.',
                'intro' => 'Pielęgnacja gabinetowa dobrana do kondycji Twojej skóry: od szybkiego nawilżenia po terapie przeciw przebarwieniom i trądzikowi.',
                'image_key' => 'tile-skin',
                'treatments' => [
                    [
                        'slug' => 'geneo',
                        'legacy_slug' => 'geneo',
                        'name' => 'Geneo+',
                        'summary' => 'Odmładzający, dotleniający i rozjaśniający zabieg w technologii MicroBubble.',
                        'description' => "Geneo+ to zabieg pielęgnacyjny, który odmładza, dotlenia i rozjaśnia skórę. Technologia MicroBubble łączy dotlenienie, złuszczenie naskórka i wchłanianie składników odżywczych.\n\nNa skórę nakładany jest odżywczy żel i kapsułka, a urządzenie wytwarza mikrobąbelki CO2, które pękają tuż przy powierzchni skóry. Zabieg nie wymaga przygotowania ani szczególnej pielęgnacji po nim i jest bezpieczny dla wszystkich typów skóry. Dostępne są dwie wersje: NeoRevive i NeoBright.",
                        'indications' => [
                            'NeoRevive: skóra sucha, matowa i wrażliwa',
                            'NeoBright: cera problematyczna i przebarwienia posłoneczne',
                        ],
                        'effects' => [
                            'Dotlenienie i dokrwienie skóry',
                            'Pobudzenie metabolizmu komórek skóry',
                            'Złuszczenie martwego naskórka i oczyszczenie ujść gruczołów łojowych',
                            'NeoRevive: nawilżenie, zmniejszenie porów, spłycenie drobnych zmarszczek',
                            'NeoBright: rozjaśnienie, ujednolicenie kolorytu, mniejsze świecenie skóry',
                        ],
                    ],
                    [
                        'slug' => 'hydra-wear',
                        'legacy_slug' => 'zabieg-hydra-wear-odbudowa-bariery-hydrolipidowej',
                        'name' => 'Hydra Wear, odbudowa bariery hydrolipidowej',
                        'summary' => 'Odbudowa uszkodzonej bariery hydrolipidowej naskórka.',
                    ],
                    [
                        'slug' => 'express-hydra',
                        'legacy_slug' => 'zabieg-express-hydra',
                        'name' => 'Express Hydra',
                        'summary' => 'Szybkie nawilżenie, rozjaśnienie i ożywienie cery.',
                    ],
                    [
                        'slug' => 'express-lunch',
                        'legacy_slug' => 'zabieg-express-lunc',
                        'name' => 'Express Lunch',
                        'summary' => 'Szybki zabieg rozświetlający i nawilżający z kwasami PHA.',
                    ],
                    [
                        'slug' => 'anty-aging',
                        'legacy_slug' => 'zabiegi-anty-aging',
                        'name' => 'Zabiegi anty-aging',
                        'summary' => 'Zabiegi przeciwstarzeniowe, rewitalizujące i wyrównujące koloryt skóry.',
                    ],
                    [
                        'slug' => 'action-young',
                        'legacy_slug' => 'action-young-tradzik-mlodzienczy-oczyszczanie',
                        'name' => 'Action Young, trądzik młodzieńczy i oczyszczanie',
                        'summary' => 'Indywidualna pielęgnacja skóry trądzikowej i regulacja pracy gruczołów łojowych.',
                    ],
                    [
                        'slug' => 'zabiegi-na-tradzik',
                        'legacy_slug' => 'zabiegi-na-tradzik',
                        'name' => 'Zabiegi na trądzik',
                        'summary' => 'Procedura dobierana do rodzaju trądziku i potrzeb skóry.',
                    ],
                    [
                        'slug' => 'lumixa',
                        'legacy_slug' => 'zabiegi-lampa-lumixa-z-zelami',
                        'name' => 'Zabiegi lampą Lumixa z żelami',
                        'summary' => 'Terapia diodami LED z żelami z chromoforami: regeneracja i energetyzacja skóry.',
                    ],
                    [
                        'slug' => 'prx-t33',
                        'legacy_slug' => 'prx-t333',
                        'name' => 'PRX-T33',
                        'summary' => 'Medyczny peeling i biorewitalizacja z mieszanką kwasów, poprawiająca jędrność i koloryt.',
                    ],
                    [
                        'slug' => 'innofacial',
                        'legacy_slug' => 'innofacial',
                        'name' => 'Innofacial',
                        'summary' => 'Hydrodermabrazja kwasowa: oczyszczanie, nawilżanie i stymulacja mikrokrążenia.',
                    ],
                    [
                        'slug' => 'peelingi-chemiczne',
                        'legacy_slug' => 'peelingi-chemiczne',
                        'name' => 'Peelingi chemiczne',
                        'summary' => 'Lekkie peelingi rozświetlające oraz mocniejsze peelingi lekarskie na przebarwienia i blizny.',
                    ],
                    [
                        'slug' => 'dermapen-4-0',
                        'legacy_slug' => 'dermapen-4-0',
                        'name' => 'Dermapen 4.0',
                        'summary' => 'Mikronakłuwanie z preparatami nawilżającymi lub rozjaśniającymi, także w połączeniu z peelingiem chemicznym.',
                    ],
                ],
            ],
            [
                'slug' => 'cialo',
                'name' => 'Ciało',
                'summary' => 'Modelowanie sylwetki i pielęgnacja skóry ciała.',
                'intro' => 'Zabiegi na ciało poprawiają jędrność skóry i pomagają modelować sylwetkę bez inwazyjnych metod.',
                'image_key' => 'philo',
                'treatments' => [
                    [
                        'slug' => 'alma-spadeep',
                        'legacy_slug' => 'alma-spadeep',
                        'name' => 'Alma SpaDeep',
                        'summary' => 'Nieinwazyjne fale radiowe do modelowania i ujędrniania ciała oraz twarzy.',
                    ],
                    [
                        'slug' => 'liporadiologia-vinci-ii',
                        'legacy_slug' => 'liporadiologia-vinci-ii',
                        'name' => 'Liporadiologia Vinci II',
                        'summary' => 'Urządzenie łączące fale radiowe, podczerwień, LED, próżnię i masaż rolkami do modelowania sylwetki.',
                    ],
                    [
                        'slug' => 'fala-uderzeniowa',
                        'legacy_slug' => 'storz-fala-uderzeniowa-w-walce-z-cellulitem',
                        'name' => 'Fala uderzeniowa Storz w walce z cellulitem',
                        'summary' => 'Fala uderzeniowa w modelowaniu sylwetki i walce z cellulitem.',
                    ],
                    [
                        'slug' => 'bezpieczne-opalanie',
                        'legacy_slug' => 'zabieg-bezpieczne-opalanie-przed-opalaniem-i-po-opalaniu',
                        'name' => 'Bezpieczne opalanie, przed i po ekspozycji na słońce',
                        'summary' => 'Przygotowanie skóry do słońca i jej regeneracja po opalaniu.',
                    ],
                ],
            ],
        ];
    }
}

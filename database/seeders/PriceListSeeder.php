<?php

namespace Database\Seeders;

use App\Models\PriceGroup;
use Illuminate\Database\Seeder;

/**
 * Cennik wg obecnej strony klienta (drzwidopiekna.pl/cennik), ceny w zł.
 *
 * Zmiany względem źródła: bez nazw handlowych leków na receptę (toksyny botulinowe i hialuronidaza
 * występują pod nazwami zabiegów), a ceny trzech preparatów toksyny zapisano jako zakresy.
 * Ceny do potwierdzenia z klientką przed publikacją.
 */
class PriceListSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->groups() as $i => $group) {
            $items = $group['items'];
            unset($group['items']);

            $record = PriceGroup::updateOrCreate(['slug' => $group['slug']], $group + ['sort_order' => $i + 1]);
            $record->items()->delete();

            foreach ($items as $j => $item) {
                $record->items()->create($item + ['sort_order' => $j + 1]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $o  sub, from, pack, packLabel, note
     * @return array<string, mixed>
     */
    private function i(string $name, ?int $min = null, ?int $max = null, array $o = []): array
    {
        return [
            'subgroup' => $o['sub'] ?? null,
            'name' => $name,
            'price_min' => $min,
            'price_max' => $max,
            'is_from' => $o['from'] ?? false,
            'pack_price' => $o['pack'] ?? null,
            'pack_label' => $o['packLabel'] ?? null,
            'note' => $o['note'] ?? null,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function groups(): array
    {
        $i = fn (...$args) => $this->i(...$args);

        return [
            [
                'slug' => 'virtue-rf',
                'name' => 'Radiofrekwencja z mikronakłuwaniem (Virtue RF)',
                'note' => 'Zakres zabiegu ustalamy indywidualnie po konsultacji.',
                'items' => [
                    $i('Twarz', 1250),
                    $i('Twarz i okolice oczu', 1500),
                    $i('Twarz i szyja', 2100),
                    $i('Twarz, szyja i dekolt', 2950),
                    $i('Okolice oczu', 950),
                    $i('Szyja i dekolt', 2100),
                    $i('Linia żuchwy (broda i szyja)', 1500),
                    $i('Ramiona, kolana lub brzuch', 1200, 2000),
                ],
            ],
            [
                'slug' => 'stymulatory-tkankowe',
                'name' => 'Stymulatory tkankowe',
                'items' => [
                    $i('Classic (okolice oczu)', 450, null, ['sub' => 'Terapia aminokwasowa (Jalupro)']),
                    $i('Classic (twarz)', 600, null, ['sub' => 'Terapia aminokwasowa (Jalupro)']),
                    $i('HMW 2 ml', 900, null, ['sub' => 'Terapia aminokwasowa (Jalupro)']),
                    $i('Young Eye', 1000, null, ['sub' => 'Terapia aminokwasowa (Jalupro)']),
                    $i('1 ampułka', 2200, null, ['sub' => 'Hydroksyapatyt wapnia (Radiesse 1,5 ml)']),
                    $i('Z NCTF 135HA', 2500, null, ['sub' => 'Hydroksyapatyt wapnia (Radiesse 1,5 ml)']),
                    $i('1 ampułka', 250, null, ['sub' => 'Tropokolagen (Guna)']),
                    $i('Sculptra, 1 ampułka', 2200, null, ['sub' => 'Kwas polimlekowy (zagęszczanie skóry, modulacja mięśni)']),
                    $i('Sculptra z NCTF 135HA', 2500, null, ['sub' => 'Kwas polimlekowy (zagęszczanie skóry, modulacja mięśni)']),
                    $i('Lenisna, 1 ampułka', 2200, null, ['sub' => 'Kwas polimlekowy (zagęszczanie skóry, modulacja mięśni)']),
                    $i('Lenisna z NCTF 135HA', 2500, null, ['sub' => 'Kwas polimlekowy (zagęszczanie skóry, modulacja mięśni)']),
                    $i('JuveLook, 1 ampułka', 1800, null, ['sub' => 'Kwas polimlekowy (zagęszczanie skóry, modulacja mięśni)']),
                ],
            ],
            [
                'slug' => 'laseroterapia',
                'name' => 'Laseroterapia',
                'items' => [
                    $i('Mały', 450, null, ['from' => true, 'sub' => 'Usuwanie tatuaży (za zabieg, czarny tusz)']),
                    $i('Średni', 650, null, ['from' => true, 'sub' => 'Usuwanie tatuaży (za zabieg, czarny tusz)']),
                    $i('Duży', 800, null, ['from' => true, 'sub' => 'Usuwanie tatuaży (za zabieg, czarny tusz)', 'note' => 'Serie i tatuaże kolorowe wyceniamy indywidualnie.']),
                    $i('1. zabieg', 500, null, ['sub' => 'Usuwanie makijażu permanentnego brwi']),
                    $i('2. i 3. zabieg', 400, null, ['sub' => 'Usuwanie makijażu permanentnego brwi']),
                    $i('Nos lub broda', 400, null, ['from' => true, 'sub' => 'Zamykanie rumienia i naczynek (kolejne zabiegi wyceniamy indywidualnie)']),
                    $i('Policzki', 600, null, ['from' => true, 'sub' => 'Zamykanie rumienia i naczynek (kolejne zabiegi wyceniamy indywidualnie)']),
                    $i('Szyja i dekolt', 900, null, ['sub' => 'Zamykanie rumienia i naczynek (kolejne zabiegi wyceniamy indywidualnie)']),
                    $i('Cała twarz', 1000, null, ['sub' => 'Zamykanie rumienia i naczynek (kolejne zabiegi wyceniamy indywidualnie)']),
                    $i('Twarz, dekolt lub dłonie', 900, null, ['sub' => 'Rozjaśnianie przebarwień']),
                    $i('Małe melasma', 400, null, ['from' => true, 'sub' => 'Rozjaśnianie przebarwień']),
                    $i('Duże melasma', 800, null, ['from' => true, 'sub' => 'Rozjaśnianie przebarwień']),
                    $i('ClearLift: twarz, szyja, dekolt, dłonie', 1000, null, ['sub' => 'Ujędrnianie skóry laserem']),
                    $i('Er:YAG ablacyjny: okolice oczu', 500, null, ['sub' => 'Ujędrnianie skóry laserem']),
                    $i('Er:YAG ablacyjny: twarz', 1000, null, ['sub' => 'Ujędrnianie skóry laserem']),
                    $i('Er:YAG ablacyjny: twarz, szyja i dekolt', 1800, null, ['sub' => 'Ujędrnianie skóry laserem']),
                    $i('Er:YAG ablacyjny: dłonie', 1000, null, ['sub' => 'Ujędrnianie skóry laserem']),
                    $i('Brzuch', 900, null, ['from' => true, 'sub' => 'Rozstępy']),
                    $i('Pośladki i boki', 800, null, ['from' => true, 'sub' => 'Rozstępy']),
                ],
            ],
            [
                'slug' => 'zmarszczki-mimiczne',
                'name' => 'Zmarszczki mimiczne, bruksizm i nadpotliwość',
                'note' => 'Cena zależy od wybranego preparatu i dawki, ustalanych podczas konsultacji.',
                'items' => [
                    $i('1 okolica', 500, 700),
                    $i('2 okolice', 1000, 1400),
                    $i('3 okolice', 1300, 2000),
                    $i('Zmarszczki „królicze” lub broda', 300),
                    $i('Uniesienie brwi', 400),
                    $i('Uśmiech dziąsłowy', 500),
                    $i('Mezoterapia mimiczna (twarz lub szyja)', 1000),
                    $i('Bruksizm', 1000, 1600, ['note' => 'Zależnie od dawki.']),
                    $i('Nadpotliwość (pachy lub dłonie)', 1800),
                ],
            ],
            [
                'slug' => 'wypelniacze',
                'name' => 'Wypełniacze z kwasem hialuronowym',
                'note' => 'Cena zależy od rodzaju i ilości preparatu dobranego podczas konsultacji. Pierwsza wizyta kontrolna po rozpuszczaniu wypełniacza jest bezpłatna.',
                'items' => [
                    $i('Dolina łez', 1000),
                    $i('Modelowanie ust', 1000),
                    $i('Nawilżenie ust', 800),
                    $i('Bruzdy nosowo-wargowe', 900, 1200),
                    $i('Linie marionetki', 1000),
                    $i('Objętość policzków', 1000, 1700),
                    $i('Modelowanie żuchwy i brody', 1500, null, ['from' => true]),
                    $i('Odmładzanie dłoni', 900, 1500),
                    $i('Lift Booster (NCTF + Fine Lines), 4 ml', 1200),
                    $i('Korekta nosa', 900, null, ['from' => true]),
                    $i('Rozpuszczanie wypełniacza, 1. zabieg', 500, 1000),
                    $i('Rozpuszczanie wypełniacza, 2. zabieg', 500, 800),
                ],
            ],
            [
                'slug' => 'mezoterapia',
                'name' => 'Mezoterapia',
                'items' => [
                    $i('Neauvia Hydro Deluxe, 2,5 ml', 750, null, ['sub' => 'Mezoterapia igłowa']),
                    $i('Zabieg na worki pod oczami', 300, null, ['sub' => 'Mezoterapia igłowa']),
                    $i('Tropokolagen (Guna)', 250, null, ['from' => true, 'sub' => 'Mezoterapia igłowa']),
                    $i('NCTF Fillmed, 3 ml', 500, null, ['sub' => 'Mezoterapia igłowa']),
                    $i('Osocze bogatopłytkowe (z oceną komórek macierzystych)', 550, 1200, ['sub' => 'Mezoterapia igłowa']),
                    $i('Lift Booster, 4 ml', 1200, null, ['sub' => 'Mezoterapia igłowa']),
                    $i('Hair+', 300, null, ['sub' => 'Mezoterapia skóry głowy (wymaga serii, planowanej na konsultacji)']),
                    $i('dr Cyj Hair Filler', 650, null, ['from' => true, 'sub' => 'Mezoterapia skóry głowy (wymaga serii, planowanej na konsultacji)']),
                    $i('Osocze bogatopłytkowe', 450, null, ['from' => true, 'sub' => 'Mezoterapia skóry głowy (wymaga serii, planowanej na konsultacji)']),
                ],
            ],
            [
                'slug' => 'komorki-macierzyste',
                'name' => 'Komórki macierzyste i czynniki wzrostu (mezoterapia osoczem)',
                'note' => 'Przy serii co najmniej 3 zabiegów obowiązuje rabat 20%.',
                'items' => [
                    $i('Skóra głowy', 450, null, ['from' => true]),
                    $i('Twarz', 650),
                    $i('Twarz i szyja lub szyja i dekolt', 800),
                    $i('Twarz, szyja i dekolt', 1000),
                    $i('Twarz z Dermapen', 1000),
                    $i('Twarz i szyja z Dermapen', 1100),
                    $i('Twarz, szyja i dekolt z Dermapen', 1300),
                ],
            ],
            [
                'slug' => 'lipoliza',
                'name' => 'Lipoliza iniekcyjna',
                'items' => [
                    $i('Aqualyx (1 ampułka = 8 ml)', 450),
                    $i('Lipo Lab+', 350),
                ],
            ],
            [
                'slug' => 'nici',
                'name' => 'Rewitalizacja skóry i lifting nićmi',
                'note' => 'Cena za jedną nić. Ostateczny koszt zależy od liczby i rodzaju nici, ustalanych podczas konsultacji.',
                'items' => [
                    $i('Nić prosta', 60),
                    $i('Nić spiralna', 80),
                    $i('Nić kolczasta (liftingująca)', 450),
                    $i('Przykład: lifting policzków i korekta owalu (4 nici kolczaste)', 1800),
                    $i('Przykład: rewitalizacja brody i szyi (nici proste i spiralne)', 800),
                ],
            ],
            [
                'slug' => 'zabiegi-na-cialo',
                'name' => 'Zabiegi na ciało',
                'note' => 'Najlepsze efekty w modelowaniu sylwetki daje seria zabiegów na przemian z drenażem limfatycznym.',
                'items' => [
                    $i('1 okolica (np. brzuch, pośladki, uda)', 400, null, ['sub' => 'Fala uderzeniowa Storz', 'pack' => 1900, 'packLabel' => 'pakiet 6 zabiegów']),
                    $i('2 okolice (np. przód i tył ud)', 600, null, ['sub' => 'Fala uderzeniowa Storz', 'pack' => 2500, 'packLabel' => 'pakiet 6 zabiegów']),
                    $i('Owal twarzy', 300, null, ['sub' => 'Fala uderzeniowa Storz', 'pack' => 1100, 'packLabel' => 'pakiet 5 zabiegów']),
                    $i('Pakiet z drenażem limfatycznym 4+4', null, null, ['sub' => 'Fala uderzeniowa Storz', 'pack' => 1800]),
                    $i('Pakiet z drenażem limfatycznym 6+6', null, null, ['sub' => 'Fala uderzeniowa Storz', 'pack' => 2500]),
                    $i('Kobido, japoński masaż liftingujący (60 min)', 300, null, ['sub' => 'Masaże']),
                    $i('Masaż relaksacyjny twarzy (30 min)', 170, null, ['sub' => 'Masaże']),
                    $i('Masaż fizjoterapeutyczny (30 min)', 100, null, ['sub' => 'Masaże']),
                    $i('Masaż fizjoterapeutyczny (60 min)', 150, null, ['sub' => 'Masaże']),
                    $i('Cała twarz', 500, null, ['sub' => 'SpaDeep', 'pack' => 1800, 'packLabel' => '4 zabiegi']),
                    $i('Owal', 350, null, ['sub' => 'SpaDeep', 'pack' => 1200, 'packLabel' => '4 zabiegi']),
                    $i('Ramiona', 450, null, ['sub' => 'SpaDeep', 'pack' => 1600, 'packLabel' => '4 zabiegi']),
                    $i('Brzuch', 450, null, ['sub' => 'SpaDeep', 'pack' => 1600, 'packLabel' => '4 zabiegi']),
                    $i('Boki', 450, null, ['sub' => 'SpaDeep', 'pack' => 1600, 'packLabel' => '4 zabiegi']),
                    $i('Pośladki', 500, null, ['sub' => 'SpaDeep', 'pack' => 1800, 'packLabel' => '4 zabiegi']),
                    $i('Uda, całe (przód i tył)', 600, null, ['sub' => 'SpaDeep', 'pack' => 2000, 'packLabel' => '4 zabiegi']),
                    $i('Owal i szyja', 500, null, ['sub' => 'SpaDeep', 'pack' => 1800, 'packLabel' => '4 zabiegi']),
                    $i('Turbo owal i szyja, RF pixel (1 zabieg)', 750, null, ['sub' => 'SpaDeep']),
                ],
            ],
            [
                'slug' => 'peelingi',
                'name' => 'Peelingi (Enerpeel)',
                'items' => [
                    $i('JR', 300),
                    $i('Kwas glikolowy 50%', 300),
                    $i('Kwas salicylowy', 300),
                    $i('TCA 30%', 350),
                    $i('Dłonie', 300),
                ],
            ],
            [
                'slug' => 'geneo',
                'name' => 'Geneo+',
                'items' => [
                    $i('NeoBright, NeoRevive lub Balance', 350),
                ],
            ],
            [
                'slug' => 'dermapen',
                'name' => 'Dermapen 4.0',
                'note' => 'Inne okolice (np. dłonie, uda) wyceniamy indywidualnie. Przy serii 3 zabiegów obowiązuje rabat 10%.',
                'items' => [
                    $i('Twarz', 800, null, ['sub' => 'Z ampułką nawilżającą lub rozjaśniającą']),
                    $i('Twarz i szyja', 900, null, ['sub' => 'Z ampułką nawilżającą lub rozjaśniającą']),
                    $i('Twarz, szyja i dekolt', 1000, null, ['sub' => 'Z ampułką nawilżającą lub rozjaśniającą']),
                    $i('Twarz', 1000, null, ['sub' => 'Z peelingiem chemicznym']),
                    $i('Twarz i szyja', 1100, null, ['sub' => 'Z peelingiem chemicznym']),
                    $i('Twarz, szyja i dekolt', 1200, null, ['sub' => 'Z peelingiem chemicznym']),
                ],
            ],
            [
                'slug' => 'lovely-cosmetics',
                'name' => 'Lovely Cosmetics',
                'items' => [
                    $i('Hydra Wear, odbudowa bariery hydrolipidowej', 350, null, ['note' => 'Seria 2–4 zabiegów co 7–10 dni.']),
                    $i('Express Hydra', 300),
                    $i('Express Lunch', 300),
                    $i('Bezpieczne opalanie (przed i po ekspozycji na słońce)', 300),
                    $i('Anty-aging', 350),
                    $i('Action Young, trądzik młodzieńczy i oczyszczanie', 300),
                    $i('Zabiegi na trądzik', 350),
                ],
            ],
            [
                'slug' => 'lumixa',
                'name' => 'Lampa Lumixa z żelami',
                'items' => [
                    $i('Skin Normaliser', 350, null, ['note' => 'Zalecany cykl: jeden zabieg tygodniowo przez 5 tygodni.']),
                    $i('Collagen Booster', 350),
                    $i('Age Control', 350),
                ],
            ],
            [
                'slug' => 'retix-c',
                'name' => 'Retix C.',
                'items' => [
                    $i('Smart DNA Therapy z mikronakłuwaniem (twarz, szyja, dekolt)', 500),
                    $i('Retibooster Therapy z mikronakłuwaniem (twarz, szyja, dekolt)', 600),
                    $i('Retibooster i Smart DNA z mikronakłuwaniem (twarz, szyja, dekolt)', 800),
                    $i('Retix Eye z mikronakłuwaniem', 350),
                ],
            ],
            [
                'slug' => 'prx-t33',
                'name' => 'PRX-T33',
                'items' => [
                    $i('Twarz', 350),
                    $i('Twarz i szyja', 390),
                    $i('Twarz, szyja i dekolt', 450),
                ],
            ],
            [
                'slug' => 'innofacial',
                'name' => 'Innofacial, oczyszczanie skóry',
                'note' => 'Zabieg można wykonać także na plecach i barkach, cenę ustalamy po konsultacji.',
                'items' => [
                    $i('Twarz z maską', 350),
                    $i('Twarz i szyja z maską', 400),
                    $i('Twarz, szyja i dekolt z maską', 450),
                ],
            ],
            [
                'slug' => 'cosmelan',
                'name' => 'Cosmelan',
                'items' => [
                    $i('1 zabieg', 1700),
                ],
            ],
        ];
    }
}

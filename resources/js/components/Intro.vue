<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { SIGNET_VIEW_BOX, door, far, frame, near, outline } from '../signet';

/*
 * Animacja powitalna. Znacznik jest zawsze w HTML (SSR), ale CSS pokazuje go tylko
 * wtedy, gdy skrypt w <head> ustawi html[data-intro="play"] (raz na sesję, bez
 * ograniczonego ruchu). Bez JS nakładka sama znika po ok. 4 s (animacja w site.css).
 *
 * Scenariusz wg storyboardu klienta (sekundy): drzwi 0 do 0,35 (wewnętrzny prostokąt), ramka drzwi 0,22 do 0,62, szlif od
 * narożników ramki na zewnątrz (najpierw krótkie przekątne 0,45 do 1, potem długie, które krzyżują się w X, 0,72 do 1,5),
 * domknięcie obrysem 1,2 do 1,65 (potem krótka pauza), nazwa 1,65, napis 2,2, od 3 s otwarcie dwóch skrzydeł drzwi, które
 * odsłaniają stronę (CSS: .ddp-intro--done). Kliknięcie lub klawisz pomija czekanie i otwiera drzwi od razu.
 *
 * Światło jest subtelne i miękkie, jak na zdjęciowych klatkach storyboardu: ciepłe światło okna i cień rośliny na całej planszy
 * (na skrzydłach drzwi), poświata za znakiem, jedna miękka smuga światła, która przesuwa się w poprzek planszy razem z rysowaniem
 * znaku i przechodzi przez jego środek, oraz delikatny jasny punkt na końcu rysowanej linii. Bez gwiazdek, twardych linii i
 * efektów „z kreskówki”. Tylko opacity, przesuw i przesuw kreski, bez animowanego obrotu i bez 3D
 * (CSS: .ddp-intro__light, __foliage, __bloom, __beam, __glints). Geometria znaku: resources/js/signet.js.
 */
const done = ref(false);
let timer = null;
let finish = () => {};

// Linie sygnetu: [ścieżka, czas rysowania w s, opóźnienie w s]. Kolejność to kolejność rysowania (środek na zewnątrz).
// Krótkie przekątne rysują się po rogach ramki zgodnie z ruchem wskazówek zegara, a długie dopełniają kolejne krzyże X
// (góra, dół, lewy bok, prawy bok). Indeksy odpowiadają kolejności w signet.js: lewy górny, prawy górny, lewy dolny, prawy dolny.
const strokes = [
    [door, 0.35, 0],
    [frame, 0.4, 0.22],
    ...[0, 1, 2, 3, 6, 7, 4, 5].map((i, k) => [near[i], 0.45, 0.45 + k * 0.04]),
    ...[0, 2, 6, 4, 1, 5, 3, 7].map((i, k) => [far[i], 0.5, 0.72 + k * 0.04]),
    [outline, 0.45, 1.2],
];

// Długość ścieżki z poleceń M, L, H, V i Z (współrzędne bezwzględne). Jasny punkt ma mieć na każdej linii tę samą długość
// (kilka jednostek znaku), a ścieżki mają pathLength="1", więc jego długość trzeba podać jako ułamek tej linii.
function pathLength(d) {
    let x = 0;
    let y = 0;
    let startX = 0;
    let startY = 0;
    let length = 0;

    for (const [, command, args] of d.matchAll(/([MLHVZ])([^MLHVZ]*)/g)) {
        const n = args.trim() ? args.trim().split(/[\s,]+/).map(Number) : [];
        let nx = x;
        let ny = y;

        if (command === 'M' || command === 'L') [nx, ny] = n;
        if (command === 'H') nx = n[0];
        if (command === 'V') ny = n[0];
        if (command === 'Z') [nx, ny] = [startX, startY];
        if (command !== 'M') length += Math.hypot(nx - x, ny - y);
        if (command === 'M') [startX, startY] = [nx, ny];

        [x, y] = [nx, ny];
    }

    return length;
}

const GLINT_LENGTH = 4;

// Cień rośliny na ścianie (jak na zdjęciowych klatkach storyboardu): kilka łukowatych liści z listkami po obu stronach osi,
// całość dwiema ścieżkami (listki i osie), rozmyta w CSS. Liście: [kąt osi w stopniach, długość, wygięcie].
// Liczone raz i bez losowania, więc SSR i przeglądarka dają to samo.
const FRONDS = [
    [-168, 118, 0.35], [-140, 134, -0.3], [-112, 146, 0.25], [-86, 128, -0.35], [-58, 140, 0.3], [-30, 112, -0.25], [-8, 92, 0.3],
];

function foliage() {
    const round = (n) => Math.round(n * 10) / 10;
    const leaflets = [];
    const stems = [];

    for (const [angle, length, bend] of FRONDS) {
        const count = 8;
        let stem = 'M0 0';

        for (let i = 1; i <= count + 1; i++) {
            const t = i / (count + 1);
            const direction = ((angle + bend * 30 * t) * Math.PI) / 180;
            const px = Math.cos(direction) * length * t;
            const py = Math.sin(direction) * length * t;

            stem += `L${round(px)} ${round(py)}`;

            if (i > count) break;

            // Listki po obu stronach osi: przy nasadzie rozwarte i krótsze, w środku najdłuższe, bliżej końca wąsko i do przodu.
            const reach = length * 0.38 * (0.5 + 0.5 * Math.sin(Math.PI * t));
            const lean = ((68 - 42 * t) * Math.PI) / 180;

            for (const side of [-1, 1]) {
                const a = direction + side * lean;
                const tx = px + Math.cos(a) * reach;
                const ty = py + Math.sin(a) * reach;
                const mx = (px + tx) / 2;
                const my = (py + ty) / 2;
                const w = reach * 0.17;
                const nx = -Math.sin(a) * w;
                const ny = Math.cos(a) * w;

                leaflets.push(`M${round(px)} ${round(py)}Q${round(mx + nx)} ${round(my + ny)} ${round(tx)} ${round(ty)}Q${round(mx - nx)} ${round(my - ny)} ${round(px)} ${round(py)}Z`);
            }
        }

        stems.push(stem);
    }

    return { leaflets: leaflets.join(''), stems: stems.join('') };
}

const plant = foliage();

// Światło leży na obu skrzydłach drzwi (każde pokazuje swoją połowę tej samej sceny), więc rozchyla się razem z nimi.
const sides = ['left', 'right'];

const timing = ([d, dur, delay]) => ({
    '--dur': `${dur}s`,
    '--delay': `${delay}s`,
    '--dash': (GLINT_LENGTH / pathLength(d)).toFixed(4),
});

function skip() {
    finish();
}

onMounted(() => {
    const root = document.documentElement;
    if (root.dataset.intro !== 'play') return;

    finish = () => {
        if (done.value) return;
        clearTimeout(timer);
        window.removeEventListener('keydown', skip);
        done.value = true;
        try {
            sessionStorage.setItem('ddp-intro', '1');
        } catch (e) {
            // tryb prywatny: pomijamy zapis
        }
        // Po otwarciu drzwi (CSS: 0,15 s zwłoki + 1,4 s ruchu) nakładka jest ukrywana, a potem zdejmujemy znacznik.
        setTimeout(() => delete root.dataset.intro, 2000);
    };

    timer = setTimeout(finish, 3000);
    window.addEventListener('keydown', skip);
});

onBeforeUnmount(() => {
    clearTimeout(timer);
    window.removeEventListener('keydown', skip);
});
</script>

<template>
    <div class="ddp-intro" :class="{ 'ddp-intro--done': done }" aria-hidden="true" @click="skip">
        <div v-for="side in sides" :key="side" class="ddp-intro__leaf" :class="`ddp-intro__leaf--${side}`">
            <div class="ddp-intro__light">
                <!-- Cień rośliny widać tylko na prawym skrzydle (jak na zdjęciowych klatkach storyboardu), więc kształt leży tylko tam. -->
                <template v-if="side === 'right'">
                    <svg class="ddp-intro__foliage ddp-intro__foliage--top" viewBox="-150 -150 300 165" focusable="false">
                        <defs>
                            <g id="ddp-intro-plant">
                                <path :d="plant.leaflets" />
                                <path class="ddp-intro__stem" :d="plant.stems" />
                            </g>
                        </defs>
                        <use href="#ddp-intro-plant" />
                    </svg>
                    <svg class="ddp-intro__foliage ddp-intro__foliage--bottom" viewBox="-150 -150 300 165" focusable="false">
                        <use href="#ddp-intro-plant" />
                    </svg>
                </template>
            </div>
        </div>
        <div class="ddp-intro__stack">
            <div class="ddp-intro__mark">
                <div class="ddp-intro__bloom"></div>
                <svg class="ddp-intro__signet" :viewBox="SIGNET_VIEW_BOX" focusable="false">
                    <path v-for="stroke in strokes" :key="stroke[0]" pathLength="1" :d="stroke[0]" :style="timing(stroke)" />

                    <!-- Delikatny jasny punkt biegnie końcem każdej rysowanej linii w tym samym tempie co ona. -->
                    <g class="ddp-intro__glints">
                        <path v-for="stroke in strokes" :key="stroke[0]" pathLength="1" :d="stroke[0]" :style="timing(stroke)" />
                    </g>
                </svg>
                <!-- Jedna miękka smuga światła przesuwa się w poprzek planszy razem z rysowaniem znaku i przechodzi przez jego środek. -->
                <div class="ddp-intro__beam"></div>
            </div>
            <div class="ddp-intro__name">Drzwi do Piękna</div>
            <div class="ddp-intro__sub">Medycyna estetyczna</div>
        </div>
    </div>
</template>

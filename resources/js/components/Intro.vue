<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

/*
 * Animacja powitalna. Znacznik jest zawsze w HTML (SSR), ale CSS pokazuje go tylko
 * wtedy, gdy skrypt w <head> ustawi html[data-intro="play"] (raz na sesję, bez
 * ograniczonego ruchu). Bez JS nakładka sama znika po ok. 4 s (animacja w site.css).
 *
 * Scenariusz wg storyboardu klienta (sekundy): drzwi 0 do 0,35, szlif od narożników drzwi na zewnątrz 0,35 do 1,4,
 * obrys 1,25 do 1,65 (potem krótka pauza), nazwa 1,65, napis 2,2, od 3 s otwarcie dwóch skrzydeł drzwi, które odsłaniają
 * stronę (CSS: .ddp-intro--done). Kliknięcie lub klawisz pomija czekanie i otwiera drzwi od razu.
 *
 * Błyski (ważne dla klienta, patrz klatki zdjęciowe storyboardu): miękka poświata światła za znakiem, jasny punkt biegnący
 * końcem każdej rysowanej linii (te same czasy co linia) oraz gwiazdki światła, które rozbłyskują w narożnikach w chwili, gdy
 * dochodzą do nich linie. Tylko opacity, skala i przesuw kreski, bez obrotu i 3D (CSS: .ddp-intro__bloom, __glints, __sparkles).
 */
const done = ref(false);
let timer = null;
let finish = () => {};

// Linie sygnetu: [ścieżka, czas rysowania w s, opóźnienie w s]. Kolejność to kolejność rysowania (środek na zewnątrz).
const strokes = [
    ['M46 48H74V102H46Z', 0.35, 0],

    ['M46 48L42 28', 0.46, 0.35],
    ['M74 48L78 28', 0.5, 0.38],
    ['M74 102L78 122', 0.44, 0.42],
    ['M46 102L42 122', 0.48, 0.45],
    ['M46 48L28 42', 0.52, 0.48],
    ['M74 48L92 42', 0.45, 0.52],
    ['M74 102L92 108', 0.5, 0.55],
    ['M46 102L28 108', 0.47, 0.58],

    ['M42 28H78L92 42V108L78 122H42L28 108V42Z', 0.58, 0.7],

    ['M42 28L30 6', 0.4, 0.86],
    ['M78 28L90 6', 0.43, 0.9],
    ['M92 42L114 30', 0.38, 0.94],
    ['M92 108L114 120', 0.41, 0.97],
    ['M78 122L90 144', 0.39, 1],
    ['M42 122L30 144', 0.36, 1.04],
    ['M28 108L6 120', 0.3, 1.08],
    ['M28 42L6 30', 0.26, 1.12],

    ['M30 6H90L114 30V120L90 144H30L6 120V30Z', 0.4, 1.25],
];

// Gwiazdki światła: [x, y, kiedy rozbłysk (s), promień ramion]. Rozbłysk przy narożnikach, do których właśnie doszły linie.
const sparkles = [
    [74, 48, 0.28, 9],
    [46, 102, 0.32, 8],
    [42, 28, 0.74, 12],
    [92, 108, 0.86, 12],
    [28, 42, 0.9, 9],
    [78, 122, 0.94, 9],
    [30, 6, 1.18, 16],
    [114, 120, 1.28, 15],
    [90, 6, 1.24, 11],
    [6, 120, 1.3, 11],
    // Końcowy błysk po domknięciu obrysu (od strony światła z lewej, jak na zdjęciowych klatkach storyboardu).
    [30, 6, 1.62, 24],
    [114, 120, 1.74, 16],
];

// Czteroramienna gwiazda z wklęsłymi bokami, o promieniu r, wyśrodkowana w (0, 0).
function star(r) {
    const w = (r * 0.13).toFixed(2);
    const l = r.toFixed(2);

    return `M0 -${l}L${w} -${w}L${l} 0L${w} ${w}L0 ${l}L-${w} ${w}L-${l} 0L-${w} -${w}Z`;
}

// Długość ścieżki z poleceń M, L, H, V i Z (współrzędne bezwzględne). Jasny punkt ma mieć na każdej linii tę samą długość
// (ok. 5 jednostek znaku), a ścieżki mają pathLength="1", więc jego długość trzeba podać jako ułamek tej linii.
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

const GLINT_LENGTH = 5;

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
        <div class="ddp-intro__leaf ddp-intro__leaf--left"></div>
        <div class="ddp-intro__leaf ddp-intro__leaf--right"></div>
        <div class="ddp-intro__bloom"></div>
        <div class="ddp-intro__stack">
            <svg class="ddp-intro__signet" viewBox="0 0 120 150" focusable="false">
                <defs>
                    <radialGradient id="ddp-intro-halo">
                        <stop offset="0" stop-color="#fffdf8" stop-opacity=".95" />
                        <stop offset=".3" stop-color="#fff0dc" stop-opacity=".7" />
                        <stop offset=".65" stop-color="#f3c9a0" stop-opacity=".28" />
                        <stop offset="1" stop-color="#f3c9a0" stop-opacity="0" />
                    </radialGradient>
                </defs>
                <path v-for="stroke in strokes" :key="stroke[0]" pathLength="1" :d="stroke[0]" :style="timing(stroke)" />

                <!-- Jasny punkt biegnie końcem każdej rysowanej linii w tym samym tempie co ona. -->
                <g class="ddp-intro__glints">
                    <path v-for="stroke in strokes" :key="stroke[0]" pathLength="1" :d="stroke[0]" :style="timing(stroke)" />
                </g>

                <g class="ddp-intro__sparkles">
                    <g v-for="(sparkle, i) in sparkles" :key="i" :transform="`translate(${sparkle[0]} ${sparkle[1]})`">
                        <circle class="ddp-intro__halo" :r="sparkle[3] * 0.95" :style="{ '--delay': `${sparkle[2]}s` }" />
                        <path class="ddp-intro__sparkle" :d="star(sparkle[3])" :style="{ '--delay': `${sparkle[2]}s` }" />
                    </g>
                </g>
            </svg>
            <div class="ddp-intro__name">Drzwi do Piękna</div>
            <div class="ddp-intro__sub">Medycyna estetyczna</div>
        </div>
    </div>
</template>

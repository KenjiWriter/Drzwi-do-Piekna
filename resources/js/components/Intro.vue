<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

/*
 * Animacja powitalna. Znacznik jest zawsze w HTML (SSR), ale CSS pokazuje go tylko
 * wtedy, gdy skrypt w <head> ustawi html[data-intro="play"] (raz na sesję, bez
 * ograniczonego ruchu). Bez JS nakładka sama znika po ok. 4 s (animacja w site.css).
 * Sceny i czasy: sygnet 0 do 1,65 s, nazwa 1,65 s, napis 2,2 s, od 3 s otwarcie dwóch skrzydeł drzwi
 * (CSS: .ddp-intro--done), które odsłaniają stronę. Kliknięcie lub klawisz pomija czekanie i otwiera drzwi od razu.
 */
const done = ref(false);
let timer = null;
let finish = () => {};

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
        <div class="ddp-intro__stack">
            <svg class="ddp-intro__signet" viewBox="0 0 120 150" focusable="false">
                <path pathLength="1" d="M46 48H74V102H46Z" style="--dur: 0.35s; --delay: 0s" />

                <path pathLength="1" d="M46 48L42 28" style="--dur: 0.46s; --delay: 0.35s" />
                <path pathLength="1" d="M74 48L78 28" style="--dur: 0.5s; --delay: 0.38s" />
                <path pathLength="1" d="M74 102L78 122" style="--dur: 0.44s; --delay: 0.42s" />
                <path pathLength="1" d="M46 102L42 122" style="--dur: 0.48s; --delay: 0.45s" />
                <path pathLength="1" d="M46 48L28 42" style="--dur: 0.52s; --delay: 0.48s" />
                <path pathLength="1" d="M74 48L92 42" style="--dur: 0.45s; --delay: 0.52s" />
                <path pathLength="1" d="M74 102L92 108" style="--dur: 0.5s; --delay: 0.55s" />
                <path pathLength="1" d="M46 102L28 108" style="--dur: 0.47s; --delay: 0.58s" />

                <path pathLength="1" d="M42 28H78L92 42V108L78 122H42L28 108V42Z" style="--dur: 0.58s; --delay: 0.7s" />

                <path pathLength="1" d="M42 28L30 6" style="--dur: 0.4s; --delay: 0.86s" />
                <path pathLength="1" d="M78 28L90 6" style="--dur: 0.43s; --delay: 0.9s" />
                <path pathLength="1" d="M92 42L114 30" style="--dur: 0.38s; --delay: 0.94s" />
                <path pathLength="1" d="M92 108L114 120" style="--dur: 0.41s; --delay: 0.97s" />
                <path pathLength="1" d="M78 122L90 144" style="--dur: 0.39s; --delay: 1s" />
                <path pathLength="1" d="M42 122L30 144" style="--dur: 0.36s; --delay: 1.04s" />
                <path pathLength="1" d="M28 108L6 120" style="--dur: 0.3s; --delay: 1.08s" />
                <path pathLength="1" d="M28 42L6 30" style="--dur: 0.26s; --delay: 1.12s" />

                <path pathLength="1" d="M30 6H90L114 30V120L90 144H30L6 120V30Z" style="--dur: 0.4s; --delay: 1.25s" />
            </svg>
            <div class="ddp-intro__name">Drzwi do Piękna</div>
            <div class="ddp-intro__sub">Medycyna estetyczna</div>
        </div>
    </div>
</template>

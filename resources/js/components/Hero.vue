<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ArrowIcon from './ArrowIcon.vue';
import Picture from './Picture.vue';
import Signet from './Signet.vue';

defineProps({
    content: { type: Object, required: true },
});

const clinic = computed(() => usePage().props.clinic);

/*
 * Strzałka przewija do pasa zalet bez dopisywania #zalety do adresu. scrollIntoView bez `behavior` zachowuje się
 * tak, jak ustawia CSS (płynnie, a przy ograniczonym ruchu w systemie natychmiast). Fokus przechodzi na pas,
 * żeby użytkownik klawiatury i czytnika ekranu został tam, gdzie przewinęła się strona. Bez JS działa zwykły link.
 */
function scrollDown(event) {
    const target = document.getElementById('zalety');

    if (!target) return;

    event.preventDefault();
    target.scrollIntoView({ block: 'start' });
    target.focus({ preventScroll: true });
}
</script>

<template>
    <section class="ddp-hero" aria-labelledby="hero-title">
        <figure v-parallax class="ddp-hero__media">
            <Picture
                name="hero"
                mobile="hero-m"
                sizes="(min-width: 1024px) 58vw, 100vw"
                mobile-sizes="100vw"
                position="58% 40%"
                eager
            />
        </figure>

        <div class="ddp-hero__copy">
            <p class="ddp-eyebrow">
                <template v-for="(line, i) in content.eyebrow" :key="i"><br v-if="i" />{{ line }}</template>
            </p>
            <h1 id="hero-title" class="ddp-h1">
                <template v-for="(line, i) in content.title" :key="i"><br v-if="i" />{{ line }}</template>
            </h1>
            <p class="ddp-lead">{{ content.lead }}</p>
            <a class="ddp-btn u-fill-sm" :href="clinic.booking_url" target="_blank" rel="noopener">
                {{ content.cta }}<span class="sr-only"> (otwiera się w nowej karcie)</span>
                <ArrowIcon />
            </a>
        </div>

        <!-- Strzałka prowadzi do pasa zalet pod hero (id="zalety" w Home.vue). Przewijanie jest płynne (site.css), chyba że system ogranicza ruch. -->
        <a class="ddp-scroll" href="#zalety" :aria-label="content.scroll" @click="scrollDown">
            <svg viewBox="0 0 48 48" aria-hidden="true" focusable="false">
                <circle cx="24" cy="24" r="23.5" />
                <path class="ddp-scroll__arrow" d="M24 15v17M17.5 26.5 24 33l6.5-6.5" />
            </svg>
        </a>

        <div class="ddp-hero__brand" aria-hidden="true">
            <Signet class="ddp-hero__brand-signet" />
            <span class="ddp-hero__brand-name">{{ clinic.name }}</span>
            <span class="ddp-hero__brand-sub">{{ clinic.tagline }}</span>
        </div>
    </section>
</template>

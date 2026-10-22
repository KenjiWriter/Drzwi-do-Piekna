<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

/*
 * Pozioma karuzela z przyciąganiem (scroll-snap) i nawigacją: kropki i strzałki.
 * Nawigacja jest tylko na telefonie (CSS), od 1024 px elementy leżą w siatce.
 */
const props = defineProps({
    label: { type: String, required: true },
    total: { type: Number, required: true }, // liczba elementów (znana z serwera, więc bez skoku układu)
    noun: { type: String, default: 'Przykład' },
});

const track = ref(null);
const index = ref(0);
let reduceMotion = false;

function items() {
    return track.value ? [...track.value.children] : [];
}

function update() {
    const el = track.value;
    if (!el) return;

    const list = items();
    const box = el.getBoundingClientRect();
    const pad = parseFloat(getComputedStyle(el).scrollPaddingLeft) || 0;

    if (el.scrollLeft + el.clientWidth >= el.scrollWidth - 2) {
        index.value = list.length - 1;
        return;
    }

    let best = 0;
    let distance = Infinity;
    list.forEach((child, i) => {
        const d = Math.abs(child.getBoundingClientRect().left - (box.left + pad));
        if (d < distance) {
            distance = d;
            best = i;
        }
    });
    index.value = best;
}

function goTo(i) {
    const list = items();
    const child = list[Math.max(0, Math.min(i, list.length - 1))];
    child?.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', inline: 'start', block: 'nearest' });
}

onMounted(() => {
    reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    update();
    window.addEventListener('resize', update);
});

onBeforeUnmount(() => window.removeEventListener('resize', update));
</script>

<template>
    <div>
        <div ref="track" class="ddp-carousel" role="group" aria-roledescription="karuzela" :aria-label="label" @scroll.passive="update">
            <slot />
        </div>
        <div v-if="props.total > 1" class="ddp-carousel__nav">
            <div class="ddp-dots">
                <button
                    v-for="n in props.total"
                    :key="n"
                    class="ddp-dot"
                    type="button"
                    :aria-label="`${noun} ${n}`"
                    :aria-current="index === n - 1"
                    @click="goTo(n - 1)"
                ></button>
            </div>
            <div class="ddp-arrows">
                <button class="ddp-arrow ddp-arrow--prev" type="button" aria-label="Poprzedni" :disabled="index === 0" @click="goTo(index - 1)">
                    <svg viewBox="0 0 24 10" aria-hidden="true"><path d="M0 5H22M18 1L22 5L18 9" /></svg>
                </button>
                <button class="ddp-arrow" type="button" aria-label="Następny" :disabled="index >= props.total - 1" @click="goTo(index + 1)">
                    <svg viewBox="0 0 24 10" aria-hidden="true"><path d="M0 5H22M18 1L22 5L18 9" /></svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, useId } from 'vue';
import { applyPrefs, currentPrefs } from '../a11y';

/*
 * Opcje dostępności: wielkość tekstu (A, A+, A++) i kontrast. Używane w panelu pod ikoną w nagłówku (A11yMenu)
 * i w menu mobilnym na najwęższych ekranach. Kilka kopii naraz trzyma ten sam stan dzięki zdarzeniu `ddp:a11y`.
 * Stan startowy czytamy dopiero po zamontowaniu, bo SSR zawsze renderuje ustawienia domyślne.
 */
const sizes = [
    { value: 'md', label: 'A', name: 'Tekst standardowy' },
    { value: 'lg', label: 'A+', name: 'Tekst większy' },
    { value: 'xl', label: 'A++', name: 'Tekst największy' },
];

const id = useId();
const text = ref('md');
const contrast = ref(false);
const textOptions = ref(null);

function sync() {
    const prefs = currentPrefs();

    text.value = prefs.text;
    contrast.value = prefs.contrast;
}

function update(changes) {
    applyPrefs({ text: text.value, contrast: contrast.value, ...changes });
}

// Przycisk „Przywróć” znika po kliknięciu, więc fokus przechodzi na pierwszą opcję zamiast przepaść.
async function reset() {
    update({ text: 'md', contrast: false });
    await nextTick();
    textOptions.value?.querySelector('button')?.focus();
}

onMounted(() => {
    sync();
    window.addEventListener('ddp:a11y', sync);
});

onBeforeUnmount(() => {
    window.removeEventListener('ddp:a11y', sync);
});
</script>

<template>
    <p class="ddp-a11y__title">Dostępność</p>

    <div class="ddp-a11y__group" role="group" :aria-labelledby="`${id}-text`">
        <p :id="`${id}-text`" class="ddp-a11y__label">Wielkość tekstu</p>
        <div ref="textOptions" class="ddp-a11y__options">
            <button
                v-for="size in sizes"
                :key="size.value"
                type="button"
                class="ddp-a11y__option"
                :class="`ddp-a11y__option--${size.value}`"
                :aria-pressed="text === size.value"
                @click="update({ text: size.value })"
            >
                <span aria-hidden="true">{{ size.label }}</span>
                <span class="sr-only">{{ size.name }}</span>
            </button>
        </div>
    </div>

    <div class="ddp-a11y__group" role="group" :aria-labelledby="`${id}-contrast`">
        <p :id="`${id}-contrast`" class="ddp-a11y__label">Kontrast</p>
        <div class="ddp-a11y__options">
            <button type="button" class="ddp-a11y__option" :aria-pressed="!contrast" @click="update({ contrast: false })">
                Standardowy
            </button>
            <button type="button" class="ddp-a11y__option" :aria-pressed="contrast" @click="update({ contrast: true })">
                Wysoki
            </button>
        </div>
    </div>

    <button v-if="text !== 'md' || contrast" type="button" class="ddp-link ddp-a11y__reset" @click="reset">
        <span class="ddp-link__text">Przywróć ustawienia</span>
    </button>
</template>

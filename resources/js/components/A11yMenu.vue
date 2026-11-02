<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import A11yOptions from './A11yOptions.vue';

/*
 * Ikona dostępności w nagłówku i panel z opcjami pod nią. Wzorzec „disclosure” (bez pułapki fokusu):
 * Esc zamyka i wraca na przycisk, kliknięcie obok albo wyjście fokusem poza panel też zamyka.
 */
const open = ref(false);
const rootEl = ref(null);
const toggleEl = ref(null);

function close({ focus = false } = {}) {
    open.value = false;
    if (focus) toggleEl.value?.focus();
}

function onKeydown(event) {
    if (event.key !== 'Escape' || !open.value) return;

    // Nie przepuszczamy Esc dalej: menu mobilne zamknęłoby się razem z panelem.
    event.stopPropagation();
    close({ focus: true });
}

function onFocusout(event) {
    if (open.value && event.relatedTarget && !rootEl.value?.contains(event.relatedTarget)) close();
}

function onPointerdown(event) {
    if (open.value && !rootEl.value?.contains(event.target)) close();
}

onMounted(() => document.addEventListener('pointerdown', onPointerdown));
onBeforeUnmount(() => document.removeEventListener('pointerdown', onPointerdown));
</script>

<template>
    <div ref="rootEl" class="ddp-a11y" @keydown="onKeydown" @focusout="onFocusout">
        <button
            ref="toggleEl"
            type="button"
            class="ddp-a11y__toggle"
            :aria-expanded="open"
            aria-controls="a11y-panel"
            @click="open = !open"
        >
            <svg viewBox="0 0 28 28" aria-hidden="true" focusable="false">
                <circle cx="14" cy="14" r="12.5" />
                <circle cx="14" cy="8.2" r="1.6" />
                <path d="M8 11.2L14 12.4L20 11.2M14 12.4V16.2M14 16.2L11 21.4M14 16.2L17 21.4" />
            </svg>
            <span class="sr-only">Dostępność: wielkość tekstu i kontrast</span>
        </button>

        <div v-show="open" id="a11y-panel" class="ddp-a11y__panel">
            <A11yOptions />
        </div>
    </div>
</template>

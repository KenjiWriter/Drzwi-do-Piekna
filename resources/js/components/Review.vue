<script setup>
import { computed, ref } from 'vue';

// Jedna opinia na raz. Nawigacja (kropki, strzałki) pojawia się dopiero przy co najmniej dwóch opiniach.
const props = defineProps({
    title: { type: String, required: true },
    items: { type: Array, required: true }, // [{ quote, author, source, source_url }]
    note: { type: String, default: null },
});

const index = ref(0);
const current = computed(() => props.items[index.value]);

function go(i) {
    index.value = Math.max(0, Math.min(i, props.items.length - 1));
}
</script>

<template>
    <div class="ddp-review">
        <h2 class="ddp-h3">{{ title }}</h2>
        <div aria-live="polite" class="ddp-review__body">
            <blockquote class="ddp-review__quote">„{{ current.quote }}”</blockquote>
            <span class="ddp-review__author">
                {{ current.author }}<template v-if="current.source">,
                    <a v-if="current.source_url" :href="current.source_url" rel="noopener nofollow">{{ current.source }}</a>
                    <template v-else>{{ current.source }}</template>
                </template>
            </span>
        </div>
        <div v-if="items.length > 1" class="ddp-carousel__nav">
            <button class="ddp-arrow ddp-arrow--prev" type="button" aria-label="Poprzednia opinia" :disabled="index === 0" @click="go(index - 1)">
                <svg viewBox="0 0 24 10" aria-hidden="true"><path d="M0 5H22M18 1L22 5L18 9" /></svg>
            </button>
            <div class="ddp-dots">
                <button
                    v-for="(item, i) in items"
                    :key="i"
                    class="ddp-dot"
                    type="button"
                    :aria-label="`Opinia ${i + 1}`"
                    :aria-current="index === i"
                    @click="go(i)"
                ></button>
            </div>
            <button class="ddp-arrow" type="button" aria-label="Następna opinia" :disabled="index >= items.length - 1" @click="go(index + 1)">
                <svg viewBox="0 0 24 10" aria-hidden="true"><path d="M0 5H22M18 1L22 5L18 9" /></svg>
            </button>
        </div>
        <p v-if="note" class="ddp-text ddp-text--sm">{{ note }}</p>
    </div>
</template>

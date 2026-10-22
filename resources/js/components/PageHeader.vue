<script setup>
import Breadcrumbs from './Breadcrumbs.vue';

// Nagłówek podstrony: jedyny h1 na stronie. Rozmiar jak H2, bo długie tytuły nie mogą dominować.
defineProps({
    title: { type: String, required: true },
    eyebrow: { type: String, default: null },
    lead: { type: String, default: null },
    crumbs: { type: Array, default: () => [] },
});
</script>

<template>
    <header class="ddp-pagehead">
        <Breadcrumbs v-if="crumbs.length" :items="crumbs" />
        <p v-if="eyebrow" class="ddp-eyebrow">{{ eyebrow }}</p>
        <h1 class="ddp-h2 pagehead-title">{{ title }}</h1>
        <p v-if="lead" class="ddp-lead">{{ lead }}</p>
        <slot />
    </header>
</template>

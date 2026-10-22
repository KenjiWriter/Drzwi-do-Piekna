<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Signet from './Signet.vue';

const props = defineProps({
    variant: { type: String, default: 'default' }, // default | stacked | on-brand
});

const clinic = computed(() => usePage().props.clinic);

const classes = computed(() => [
    'ddp-logo',
    { 'ddp-logo--stacked': props.variant === 'stacked', 'ddp-logo--on-brand': props.variant === 'on-brand' },
]);
</script>

<template>
    <Link href="/" :class="classes">
        <Signet class="ddp-logo__signet" />
        <span class="ddp-logo__text">
            <span class="ddp-logo__name">{{ clinic.name }}</span>
            <span class="ddp-logo__sub">{{ clinic.tagline }}</span>
        </span>
    </Link>
</template>

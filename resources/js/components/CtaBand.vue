<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ArrowIcon from './ArrowIcon.vue';
import Signet from './Signet.vue';

// Burgundowy pas z hasłem i dwiema akcjami: rezerwacja online i formularz kontaktowy.
defineProps({
    title: { type: String, required: true },
    text: { type: String, default: null },
    write: { type: Boolean, default: true }, // pokaż link do formularza (wyłącz na stronie kontaktu)
});

const clinic = computed(() => usePage().props.clinic);
</script>

<template>
    <section class="ddp-band ddp-on-brand">
        <Signet class="ddp-band__mark" />
        <div class="ddp-band__inner">
            <h2 class="ddp-quote ddp-band__title">{{ title }}</h2>
            <p v-if="text" class="ddp-text">{{ text }}</p>
            <div class="band-actions">
                <a class="ddp-btn ddp-btn--on-brand u-fill-sm" :href="clinic.booking_url" target="_blank" rel="noopener">
                    Umów wizytę<span class="sr-only"> (otwiera się w nowej karcie)</span>
                    <ArrowIcon />
                </a>
                <Link v-if="write" class="ddp-link" href="/kontakt">
                    <span class="ddp-link__text">Napisz do nas</span>
                </Link>
            </div>
        </div>
    </section>
</template>

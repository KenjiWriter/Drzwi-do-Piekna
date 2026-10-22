<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const clinic = computed(() => usePage().props.clinic);
</script>

<template>
    <div class="ddp-actionbar">
        <a class="ddp-btn ddp-btn--outline" :href="`tel:${clinic.phone_e164}`">Zadzwoń</a>
        <a class="ddp-btn" :href="clinic.booking_url" target="_blank" rel="noopener">
            Umów wizytę<span class="sr-only"> (otwiera się w nowej karcie)</span>
        </a>
    </div>
</template>

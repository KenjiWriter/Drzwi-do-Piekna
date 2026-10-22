<script setup>
import Picture from './Picture.vue';

// Zdjęcie obok panelu tekstu. `flip` stawia zdjęcie po prawej (od 1024 px).
defineProps({
    image: { type: String, required: true },
    position: { type: String, default: null },
    flip: { type: Boolean, default: false },
    labelledby: { type: String, default: null },
});
</script>

<template>
    <section class="ddp-split" :class="{ 'ddp-split--flip': flip }" :aria-labelledby="labelledby">
        <figure class="ddp-split__media">
            <Picture :name="image" :position="position" sizes="(min-width: 1024px) 50vw, 100vw" />
        </figure>
        <div class="ddp-split__panel">
            <slot />
        </div>
    </section>
</template>

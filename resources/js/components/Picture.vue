<script setup>
import { computed } from 'vue';
import images from '../images.json';

/*
 * Zdjęcie z manifestu generowanego przez `npm run images` (klucz) albo z biblioteki zdjęć z panelu
 * (obiekt z serwera, ten sam kształt): srcset, wymiary (bez skoku układu) i kolor dominujący jako tło
 * na czas ładowania. `mobile` to klucz pionowego kadru dla telefonu.
 */
const props = defineProps({
    name: { type: [String, Object], required: true },
    mobile: { type: String, default: null },
    sizes: { type: String, default: '100vw' },
    mobileSizes: { type: String, default: '100vw' },
    alt: { type: String, default: null },
    eager: { type: Boolean, default: false },
    position: { type: String, default: null },
});

const main = computed(() => (typeof props.name === 'string' ? images[props.name] : props.name));
const small = computed(() => (props.mobile ? images[props.mobile] : null));
const altText = computed(() => props.alt ?? main.value?.alt ?? '');
</script>

<template>
    <picture v-if="main">
        <source v-if="small" media="(max-width: 1023px)" :srcset="small.srcset" :sizes="mobileSizes" type="image/webp" />
        <img
            :src="main.src"
            :srcset="main.srcset"
            :sizes="sizes"
            :width="main.width"
            :height="main.height"
            :alt="altText"
            :loading="eager ? 'eager' : 'lazy'"
            :fetchpriority="eager ? 'high' : null"
            decoding="async"
            :style="{ objectPosition: position, backgroundColor: main.color }"
        />
    </picture>
</template>

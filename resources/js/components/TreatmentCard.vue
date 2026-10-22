<script setup>
import ArrowIcon from './ArrowIcon.vue';
import Picture from './Picture.vue';

defineProps({
    title: { type: String, required: true },
    image: { type: [String, Object], required: true },
    href: { type: String, default: null },
});
</script>

<template>
    <component :is="href ? 'a' : 'div'" class="ddp-card" :href="href">
        <div class="ddp-card__media">
            <Picture :name="image" sizes="(min-width: 1024px) 24vw, 50vw" />
        </div>
        <div class="ddp-card__scrim"></div>
        <div class="ddp-card__body">
            <h3 class="ddp-card__title">{{ title }}</h3>
            <ArrowIcon v-if="href" />
        </div>
    </component>
</template>

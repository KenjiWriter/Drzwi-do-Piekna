<script setup>
import Icon from './Icon.vue';

defineProps({
    items: { type: Array, required: true }, // [{ icon, title, text }]
});
</script>

<template>
    <section class="ddp-trust" aria-label="Zalety">
        <div v-for="item in items" :key="item.title" class="ddp-trust__item">
            <Icon :name="item.icon" />
            <h3 class="ddp-trust__title">{{ item.title }}</h3>
            <p class="ddp-trust__text">{{ item.text }}</p>
        </div>
    </section>
</template>

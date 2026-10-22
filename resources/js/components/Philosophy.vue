<script setup>
import { Link } from '@inertiajs/vue3';
import ArrowIcon from './ArrowIcon.vue';
import Picture from './Picture.vue';

defineProps({
    title: { type: String, required: true },
    text: { type: String, default: null },
    link: { type: Object, default: null }, // { label, href }
    image: { type: String, required: true },
});
</script>

<template>
    <section class="ddp-philo ddp-on-brand" aria-labelledby="philo-title">
        <figure class="ddp-philo__media">
            <Picture :name="image" sizes="(min-width: 1024px) 58vw, 100vw" />
        </figure>
        <div class="ddp-philo__copy">
            <h2 id="philo-title" class="ddp-quote ddp-philo__title">{{ title }}</h2>
            <p v-if="text" class="ddp-text philo-text">{{ text }}</p>
            <Link v-if="link" class="ddp-link" :href="link.href">
                <span class="ddp-link__text">{{ link.label }}</span>
                <ArrowIcon />
            </Link>
        </div>
    </section>
</template>

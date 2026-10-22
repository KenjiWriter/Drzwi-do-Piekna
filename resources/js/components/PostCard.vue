<script setup>
import { Link } from '@inertiajs/vue3';
import ArrowIcon from './ArrowIcon.vue';
import Picture from './Picture.vue';

// Karta wpisu na liście. Cała karta jest klikalna przez link w tytule (rozciągnięty ::after),
// a "Czytaj dalej" to tylko zachęta, żeby czytnik ekranu nie czytał dwóch linków do tego samego adresu.
defineProps({
    post: { type: Object, required: true }, // { title, excerpt, url, photo, published_iso, published_label, minutes }
    readMore: { type: String, required: true },
    minutes: { type: String, required: true },
});
</script>

<template>
    <article class="ddp-postcard">
        <div v-if="post.photo" class="ddp-postcard__media">
            <Picture :name="post.photo" sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 100vw" />
        </div>
        <div class="ddp-postcard__body">
            <p class="ddp-postcard__meta">
                <time :datetime="post.published_iso">{{ post.published_label }}</time> · {{ post.minutes }} {{ minutes }}
            </p>
            <h2 class="ddp-postcard__title">
                <Link :href="post.url">{{ post.title }}</Link>
            </h2>
            <p class="ddp-text ddp-text--sm">{{ post.excerpt }}</p>
            <span class="ddp-link" aria-hidden="true">
                <span class="ddp-link__text">{{ readMore }}</span>
                <ArrowIcon />
            </span>
        </div>
    </article>
</template>

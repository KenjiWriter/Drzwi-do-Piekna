<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import CtaBand from '../../components/CtaBand.vue';
import PageHeader from '../../components/PageHeader.vue';
import Picture from '../../components/Picture.vue';
import SiteLayout from '../../layouts/SiteLayout.vue';

defineOptions({ layout: SiteLayout });

const props = defineProps({
    content: { type: Object, required: true },
    post: { type: Object, required: true },
    seo: { type: Object, required: true },
});

const crumbs = computed(() => [
    { name: 'Blog', url: '/blog' },
    { name: props.post.title, url: props.post.url },
]);
</script>

<template>
    <PageHeader :eyebrow="content.eyebrow" :title="post.title" :lead="post.excerpt" :crumbs="crumbs">
        <p class="ddp-post__meta">
            <time :datetime="post.published_iso">{{ post.published_label }}</time> · {{ post.minutes }} {{ content.minutes }}
        </p>
    </PageHeader>

    <article class="ddp-post">
        <figure v-if="post.photo" class="ddp-post__photo">
            <Picture :name="post.photo" sizes="(min-width: 1024px) 720px, 100vw" eager />
        </figure>

        <!-- HTML pochodzi z edytora wizualnego i jest oczyszczany po stronie serwera (App\Support\PostContent). -->
        <div class="ddp-prose" v-html="post.html"></div>

        <footer class="ddp-post__author">
            <p class="ddp-post__name">{{ post.author.name }}</p>
            <p v-if="post.author.role" class="ddp-eyebrow">{{ post.author.role }}</p>
        </footer>

        <Link class="ddp-link" href="/blog">
            <span class="ddp-link__text">{{ content.back }}</span>
        </Link>
    </article>

    <CtaBand v-reveal :title="content.cta.title" :text="content.cta.text" />
</template>

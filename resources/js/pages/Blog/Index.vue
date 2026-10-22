<script setup>
import CtaBand from '../../components/CtaBand.vue';
import PageHeader from '../../components/PageHeader.vue';
import PostCard from '../../components/PostCard.vue';
import SiteLayout from '../../layouts/SiteLayout.vue';

defineOptions({ layout: SiteLayout });

defineProps({
    content: { type: Object, required: true },
    posts: { type: Array, required: true },
    seo: { type: Object, required: true },
});
</script>

<template>
    <PageHeader :eyebrow="content.eyebrow" :title="content.title" :lead="content.lead" :crumbs="[{ name: 'Blog', url: '/blog' }]" />

    <section class="ddp-section ddp-section--tight-top" aria-label="Wpisy bloga">
        <div class="ddp-section__inner">
            <ul v-if="posts.length" class="ddp-posts">
                <li v-for="post in posts" :key="post.url">
                    <PostCard :post="post" :read-more="content.read_more" :minutes="content.minutes" />
                </li>
            </ul>
            <p v-else class="ddp-text">{{ content.empty }}</p>
        </div>
    </section>

    <CtaBand v-reveal :title="content.cta.title" :text="content.cta.text" />
</template>

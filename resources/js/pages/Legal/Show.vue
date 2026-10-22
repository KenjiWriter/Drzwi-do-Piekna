<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import PageHeader from '../../components/PageHeader.vue';
import SiteLayout from '../../layouts/SiteLayout.vue';

defineOptions({ layout: SiteLayout });

const props = defineProps({
    content: { type: Object, required: true },
    page: { type: Object, required: true }, // { title, html, toc, draft, effective_label, updated_label }
    seo: { type: Object, required: true },
});

const crumbs = computed(() => [{ name: props.page.title, url: props.seo.canonical.replace(/^https?:\/\/[^/]+/, '') }]);
</script>

<template>
    <PageHeader :eyebrow="content.eyebrow" :title="page.title" :lead="content.lead" :crumbs="crumbs">
        <p class="ddp-post__meta">
            <template v-if="page.effective_label">{{ content.effective }} {{ page.effective_label }}</template>
            <template v-else>{{ content.no_date }}</template>
            · {{ content.updated }} {{ page.updated_label }}
        </p>
    </PageHeader>

    <article class="ddp-post">
        <!-- Dopóki dokument jest wersją roboczą (czeka na prawnika albo ma pola w nawiasach), mówimy o tym wprost. -->
        <p v-if="page.draft" class="ddp-notice" role="note">
            <strong>{{ content.draft_title }}</strong> {{ content.draft_text }}
        </p>

        <nav v-if="page.toc.length" class="ddp-toc" :aria-label="content.toc">
            <p class="ddp-toc__title">{{ content.toc }}</p>
            <ol>
                <li v-for="item in page.toc" :key="item.id">
                    <a :href="`#${item.id}`">{{ item.title }}</a>
                </li>
            </ol>
        </nav>

        <!-- HTML pochodzi z edytora w panelu i przechodzi przez sanityzator po stronie serwera (App\Support\PostContent). -->
        <div class="ddp-prose" v-html="page.html"></div>

        <p class="ddp-text ddp-text--sm">
            {{ content.questions }}
            <Link href="/kontakt">{{ content.contact }}</Link>
        </p>
    </article>
</template>

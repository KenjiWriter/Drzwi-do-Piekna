<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ArrowIcon from '../../components/ArrowIcon.vue';
import PageHeader from '../../components/PageHeader.vue';
import Picture from '../../components/Picture.vue';
import SiteLayout from '../../layouts/SiteLayout.vue';

defineOptions({ layout: SiteLayout });

const props = defineProps({
    content: { type: Object, required: true },
    treatment: { type: Object, required: true },
    seo: { type: Object, required: true },
});

const clinic = computed(() => usePage().props.clinic);

const crumbs = computed(() => [
    { name: 'Zabiegi', url: '/zabiegi' },
    { name: props.treatment.category.name, url: props.treatment.category.url },
    { name: props.treatment.name, url: props.seo.canonical.replace(/^https?:\/\/[^/]+/, '') },
]);
</script>

<template>
    <PageHeader :eyebrow="treatment.category.name" :title="treatment.name" :lead="treatment.summary" :crumbs="crumbs" />

    <div class="ddp-article">
        <div class="ddp-article__main">
            <figure v-if="treatment.photo" class="ddp-article__photo">
                <Picture :name="treatment.photo" sizes="(min-width: 1024px) 640px, 100vw" />
            </figure>

            <p v-for="paragraph in treatment.paragraphs" :key="paragraph" class="ddp-text">{{ paragraph }}</p>

            <section v-if="treatment.indications.length" aria-labelledby="ind-title">
                <h2 id="ind-title" class="ddp-h4 article-h">{{ content.indications }}</h2>
                <ul class="ddp-checks">
                    <li v-for="item in treatment.indications" :key="item">{{ item }}</li>
                </ul>
            </section>

            <section v-if="treatment.effects.length" aria-labelledby="eff-title">
                <h2 id="eff-title" class="ddp-h4 article-h">{{ content.effects }}</h2>
                <ul class="ddp-checks">
                    <li v-for="item in treatment.effects" :key="item">{{ item }}</li>
                </ul>
            </section>

            <p v-if="!treatment.paragraphs.length" class="ddp-text">{{ content.consult }}</p>
        </div>

        <aside class="ddp-aside" aria-label="Umów zabieg">
            <dl v-if="treatment.duration" class="ddp-facts">
                <dt>{{ content.duration }}</dt>
                <dd>{{ treatment.duration }}</dd>
            </dl>
            <p class="ddp-text ddp-text--sm">{{ content.consult }}</p>
            <a class="ddp-btn u-fill-sm" :href="clinic.booking_url" target="_blank" rel="noopener">
                Umów konsultację<span class="sr-only"> (otwiera się w nowej karcie)</span>
                <ArrowIcon />
            </a>
            <Link class="ddp-link" href="/cennik">
                <span class="ddp-link__text">{{ content.prices_link }}</span>
            </Link>
        </aside>
    </div>
</template>

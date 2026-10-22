<script setup>
import { Link } from '@inertiajs/vue3';
import ArrowIcon from '../../components/ArrowIcon.vue';
import CtaBand from '../../components/CtaBand.vue';
import PageHeader from '../../components/PageHeader.vue';
import SiteLayout from '../../layouts/SiteLayout.vue';

defineOptions({ layout: SiteLayout });

const props = defineProps({
    content: { type: Object, required: true },
    category: { type: Object, required: true },
    seo: { type: Object, required: true },
});

const crumbs = [
    { name: 'Zabiegi', url: '/zabiegi' },
    { name: props.category.name, url: props.category.url },
];
</script>

<template>
    <PageHeader :eyebrow="content.eyebrow" :title="category.name" :lead="category.intro || category.summary" :crumbs="crumbs" />

    <section class="ddp-section ddp-section--tight-top" aria-label="Zabiegi w tym obszarze">
        <div class="ddp-section__inner">
            <ul class="ddp-rows">
                <li v-for="treatment in category.treatments" :key="treatment.url" class="ddp-row">
                    <Link :href="treatment.url">
                        <div>
                            <h2 class="ddp-row__name">{{ treatment.name }}</h2>
                            <p class="ddp-row__text">{{ treatment.summary }}</p>
                        </div>
                        <ArrowIcon />
                    </Link>
                </li>
            </ul>
            <Link class="ddp-link back-link" href="/zabiegi">
                <span class="ddp-link__text">{{ content.back }}</span>
            </Link>
        </div>
    </section>

    <CtaBand v-reveal :title="content.cta.title" :text="content.cta.text" />
</template>

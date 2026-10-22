<script setup>
import { Link } from '@inertiajs/vue3';
import ArrowIcon from '../../components/ArrowIcon.vue';
import CtaBand from '../../components/CtaBand.vue';
import PageHeader from '../../components/PageHeader.vue';
import TreatmentCard from '../../components/TreatmentCard.vue';
import SiteLayout from '../../layouts/SiteLayout.vue';

defineOptions({ layout: SiteLayout });

defineProps({
    content: { type: Object, required: true },
    categories: { type: Array, required: true },
    seo: { type: Object, required: true },
});
</script>

<template>
    <PageHeader :eyebrow="content.eyebrow" :title="content.title" :lead="content.lead" :crumbs="[{ name: 'Zabiegi', url: '/zabiegi' }]" />

    <section class="ddp-section ddp-section--tight-top" aria-label="Obszary zabiegów">
        <div class="ddp-section__inner">
            <div class="ddp-tiles ddp-tiles--5">
                <TreatmentCard v-for="category in categories" :key="category.slug" :title="category.name" :image="category.image || 'philo'" :href="category.url" />
            </div>
        </div>
    </section>

    <section v-for="category in categories" :id="category.slug" :key="category.slug" v-reveal class="ddp-section ddp-section--tight-top" :aria-labelledby="`${category.slug}-title`">
        <div class="ddp-section__inner">
            <div class="ddp-section__head">
                <div class="stack">
                    <h2 :id="`${category.slug}-title`" class="ddp-h3">{{ category.name }}</h2>
                    <p class="ddp-text">{{ category.summary }}</p>
                </div>
                <Link class="ddp-link" :href="category.url">
                    <span class="ddp-link__text">{{ category.name }}</span>
                    <ArrowIcon />
                </Link>
            </div>
            <ul class="ddp-rows">
                <li v-for="treatment in category.treatments" :key="treatment.url" class="ddp-row">
                    <Link :href="treatment.url">
                        <div>
                            <h3 class="ddp-row__name">{{ treatment.name }}</h3>
                            <p class="ddp-row__text">{{ treatment.summary }}</p>
                        </div>
                        <ArrowIcon />
                    </Link>
                </li>
            </ul>
        </div>
    </section>

    <CtaBand v-reveal :title="content.cta.title" :text="content.cta.text" />
</template>

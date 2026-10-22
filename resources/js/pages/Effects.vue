<script setup>
import BeforeAfter from '../components/BeforeAfter.vue';
import Carousel from '../components/Carousel.vue';
import CtaBand from '../components/CtaBand.vue';
import PageHeader from '../components/PageHeader.vue';
import SiteLayout from '../layouts/SiteLayout.vue';

defineOptions({ layout: SiteLayout });

defineProps({
    content: { type: Object, required: true },
    seo: { type: Object, required: true },
});
</script>

<template>
    <PageHeader :eyebrow="content.eyebrow" :title="content.title" :lead="content.lead" :crumbs="[{ name: 'Efekty', url: '/efekty' }]" />

    <section class="ddp-section ddp-section--tight-top" aria-label="Przykłady efektów">
        <div class="ddp-section__inner">
            <Carousel label="Efekty, przykłady" :total="content.items.length">
                <BeforeAfter v-for="item in content.items" :key="item.image" :name="item.image" :alt="item.alt" />
            </Carousel>
            <p class="ddp-text ddp-text--sm effects-note">{{ content.note }}</p>
        </div>
    </section>

    <CtaBand v-reveal :title="content.cta.title" :text="content.cta.text" />
</template>

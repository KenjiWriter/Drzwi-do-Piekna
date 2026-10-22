<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ArrowIcon from '../components/ArrowIcon.vue';
import BeforeAfter from '../components/BeforeAfter.vue';
import Carousel from '../components/Carousel.vue';
import Hero from '../components/Hero.vue';
import Philosophy from '../components/Philosophy.vue';
import Review from '../components/Review.vue';
import SplitSection from '../components/SplitSection.vue';
import TreatmentCard from '../components/TreatmentCard.vue';
import TrustStrip from '../components/TrustStrip.vue';
import SiteLayout from '../layouts/SiteLayout.vue';

defineOptions({ layout: SiteLayout });

defineProps({
    content: { type: Object, required: true },
    seo: { type: Object, required: true },
});

const clinic = computed(() => usePage().props.clinic);
</script>

<template>
    <Hero :content="content.hero" />

    <TrustStrip id="zalety" tabindex="-1" v-reveal :items="content.trust.items" />

    <SplitSection id="o-mnie" v-reveal image="interior" position="50% 58%" labelledby="about-title">
        <p class="ddp-eyebrow">{{ content.about.eyebrow }}</p>
        <h2 id="about-title" class="ddp-h2">{{ content.about.title }}</h2>
        <p class="ddp-text">{{ content.about.text }}</p>
        <Link class="ddp-btn ddp-btn--outline u-fill-sm" :href="content.about.href">
            {{ content.about.cta }}
            <ArrowIcon />
        </Link>
    </SplitSection>

    <section id="zabiegi" v-reveal class="ddp-section" aria-labelledby="treatments-title">
        <div class="ddp-section__inner">
            <div class="ddp-section__head">
                <div class="stack">
                    <p class="ddp-eyebrow">{{ content.treatments.eyebrow }}</p>
                    <h2 id="treatments-title" class="ddp-h2 ddp-section__title">{{ content.treatments.title }}</h2>
                </div>
                <Link class="ddp-link" :href="content.treatments.link.href">
                    <span class="ddp-link__text">{{ content.treatments.link.label }}</span>
                    <ArrowIcon />
                </Link>
            </div>
            <div class="ddp-tiles">
                <TreatmentCard v-for="item in content.treatments.items" :key="item.title" :title="item.title" :image="item.image" :href="item.href" />
            </div>
        </div>
    </section>

    <SplitSection v-reveal image="plant" flip labelledby="journey-title">
        <h2 id="journey-title" class="ddp-h2">
            <template v-for="(line, i) in content.journey.title" :key="i"><br v-if="i" />{{ line }}</template>
        </h2>
        <p class="ddp-text">{{ content.journey.text }}</p>
        <a class="ddp-btn ddp-btn--outline u-fill-sm" :href="clinic.booking_url" target="_blank" rel="noopener">
            {{ content.journey.cta }}<span class="sr-only"> (otwiera się w nowej karcie)</span>
            <ArrowIcon />
        </a>
    </SplitSection>

    <section id="efekty" v-reveal class="ddp-section" aria-labelledby="effects-title">
        <div class="ddp-section__inner">
            <div class="ddp-section__head">
                <div class="stack">
                    <p class="ddp-eyebrow">{{ content.effects.eyebrow }}</p>
                    <h2 id="effects-title" class="ddp-h2 ddp-section__title">{{ content.effects.title }}</h2>
                </div>
                <Link class="ddp-link" :href="content.effects.link.href">
                    <span class="ddp-link__text">{{ content.effects.link.label }}</span>
                    <ArrowIcon />
                </Link>
            </div>
            <Carousel label="Efekty, przykłady" :total="content.effects.items.length">
                <BeforeAfter v-for="item in content.effects.items" :key="item.image" :name="item.image" :alt="item.alt" />
            </Carousel>
            <p class="ddp-text ddp-text--sm effects-note">{{ content.effects.note }}</p>
        </div>
    </section>

    <Philosophy v-reveal :title="content.philosophy.title" :link="content.philosophy.link" image="philo" />

    <section v-if="content.reviews" v-reveal class="ddp-section ddp-section--alt" aria-label="Opinie">
        <Review :title="content.reviews.title" :items="content.reviews.items" :note="content.reviews.note" />
    </section>

    <SplitSection id="kontakt" v-reveal image="vase" flip labelledby="contact-title">
        <p class="ddp-eyebrow">{{ content.contact.eyebrow }}</p>
        <h2 id="contact-title" class="ddp-h2">{{ content.contact.title }}</h2>
        <p class="ddp-text">{{ content.contact.text }}</p>
        <address class="contact-lines">
            ul. {{ clinic.address.street }}, {{ clinic.address.postal_code }} {{ clinic.address.city }}<br />
            <a :href="`tel:${clinic.phone_e164}`">{{ clinic.phone }}</a> ·
            <a :href="`mailto:${clinic.email}`">{{ clinic.email }}</a>
        </address>
        <a class="ddp-btn u-fill-sm" :href="clinic.booking_url" target="_blank" rel="noopener">
            {{ content.contact.cta }}<span class="sr-only"> (otwiera się w nowej karcie)</span>
            <ArrowIcon />
        </a>
        <Link class="ddp-link" href="/kontakt">
            <span class="ddp-link__text">{{ content.contact.write }}</span>
        </Link>
    </SplitSection>
</template>

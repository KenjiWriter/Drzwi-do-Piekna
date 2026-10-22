<script setup>
import CtaBand from '../components/CtaBand.vue';
import PageHeader from '../components/PageHeader.vue';
import Philosophy from '../components/Philosophy.vue';
import Picture from '../components/Picture.vue';
import SplitSection from '../components/SplitSection.vue';
import SiteLayout from '../layouts/SiteLayout.vue';

defineOptions({ layout: SiteLayout });

defineProps({
    content: { type: Object, required: true },
    team: { type: Array, required: true },
    seo: { type: Object, required: true },
});

// Monogram z inicjałów: "dr Anita Symonowicz" daje "AS".
function initials(name) {
    return name
        .replace(/^dr\s+/i, '')
        .split(/\s+/)
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}
</script>

<template>
    <PageHeader :eyebrow="content.eyebrow" :title="content.title" :lead="content.lead" :crumbs="[{ name: 'O mnie', url: '/o-mnie' }]" />

    <SplitSection image="interior" position="50% 58%" labelledby="doctor-title">
        <p class="ddp-eyebrow">{{ content.doctor.eyebrow }}</p>
        <h2 id="doctor-title" class="ddp-h2">dr Anita Symonowicz</h2>
        <p v-for="paragraph in content.doctor.text" :key="paragraph" class="ddp-text">{{ paragraph }}</p>
        <ul class="ddp-checks">
            <li v-for="fact in content.doctor.facts" :key="fact">{{ fact }}</li>
        </ul>
    </SplitSection>

    <section v-reveal class="ddp-section" aria-labelledby="values-title">
        <div class="ddp-section__inner">
            <h2 id="values-title" class="ddp-h3 values-title">{{ content.values.title }}</h2>
            <div class="ddp-values">
                <div v-for="item in content.values.items" :key="item.title" class="ddp-values__item">
                    <h3 class="ddp-values__title">{{ item.title }}</h3>
                    <p class="ddp-values__text">{{ item.text }}</p>
                </div>
            </div>
        </div>
    </section>

    <Philosophy id="filozofia" v-reveal :title="content.philosophy.title" :text="content.philosophy.text" image="philo" />

    <section v-if="team.length" id="zespol" v-reveal class="ddp-section ddp-section--alt" aria-labelledby="team-title">
        <div class="ddp-section__inner">
            <div class="ddp-section__head">
                <div class="stack">
                    <p class="ddp-eyebrow">{{ content.team.eyebrow }}</p>
                    <h2 id="team-title" class="ddp-h2 ddp-section__title">{{ content.team.title }}</h2>
                </div>
            </div>
            <div class="ddp-team">
                <article v-for="person in team" :key="person.slug" class="ddp-person">
                    <div v-if="person.photo" class="ddp-person__photo">
                        <Picture :name="person.photo" sizes="112px" />
                    </div>
                    <div v-else class="ddp-person__mono" aria-hidden="true">{{ initials(person.name) }}</div>
                    <h3 class="ddp-person__name">{{ person.name }}</h3>
                    <p v-if="person.role" class="ddp-eyebrow">{{ person.role }}</p>
                    <p v-if="person.bio" class="ddp-text ddp-text--sm">{{ person.bio }}</p>
                </article>
            </div>
        </div>
    </section>

    <CtaBand v-reveal :title="content.cta.title" :text="content.cta.text" />
</template>

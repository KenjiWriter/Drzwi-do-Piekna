<script setup>
import CtaBand from '../components/CtaBand.vue';
import PageHeader from '../components/PageHeader.vue';
import SiteLayout from '../layouts/SiteLayout.vue';

defineOptions({ layout: SiteLayout });

const props = defineProps({
    content: { type: Object, required: true },
    groups: { type: Array, required: true },
    seo: { type: Object, required: true },
});

// Twarda spacja jako separator tysięcy: ten sam wynik na serwerze (SSR) i w przeglądarce.
function zl(n) {
    return `${String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')} zł`;
}

function price(item) {
    if (item.min === null) return item.pack !== null ? zl(item.pack) : props.content.consult;
    const prefix = item.from ? `${props.content.from} ` : '';

    return item.max ? `${zl(item.min).replace(' zł', '')}–${zl(item.max)}` : `${prefix}${zl(item.min)}`;
}
</script>

<template>
    <PageHeader :eyebrow="content.eyebrow" :title="content.title" :lead="content.lead" :crumbs="[{ name: 'Cennik', url: '/cennik' }]" />

    <div class="ddp-pricelayout">
        <nav aria-label="Grupy zabiegów">
            <ul class="ddp-pricenav">
                <li v-for="group in groups" :key="group.slug">
                    <a :href="`#${group.slug}`">{{ group.name.replace(/\s*\(.*\)$/, '') }}</a>
                </li>
            </ul>
        </nav>

        <div class="ddp-prices">
            <section v-for="group in groups" :id="group.slug" :key="group.slug" class="ddp-pgroup" :aria-labelledby="`${group.slug}-title`">
                <h2 :id="`${group.slug}-title`" class="ddp-pgroup__title">{{ group.name }}</h2>
                <p v-if="group.note" class="ddp-pgroup__note">{{ group.note }}</p>

                <template v-for="(section, i) in group.sections" :key="i">
                    <h3 v-if="section.name" class="ddp-psub">{{ section.name }}</h3>
                    <div v-for="(item, j) in section.items" :key="j" class="ddp-prow">
                        <div class="ddp-prow__name">
                            {{ item.name }}
                            <span v-if="item.note" class="ddp-prow__note">{{ item.note }}</span>
                        </div>
                        <div class="ddp-prow__price">
                            {{ price(item) }}
                            <span v-if="item.pack !== null && item.min !== null" class="ddp-prow__pack">
                                {{ item.packLabel || content.pack }}: {{ zl(item.pack) }}
                            </span>
                            <span v-else-if="item.min === null && item.pack !== null && item.packLabel" class="ddp-prow__pack">{{ item.packLabel }}</span>
                        </div>
                    </div>
                </template>
            </section>

            <section class="ddp-pgroup" aria-labelledby="notes-title">
                <h2 id="notes-title" class="ddp-pgroup__title">{{ content.notes_title }}</h2>
                <ul class="ddp-checks prices-notes">
                    <li v-for="note in content.notes" :key="note">{{ note }}</li>
                </ul>
            </section>
        </div>
    </div>

    <CtaBand v-reveal :title="content.cta.title" :text="content.cta.text" />
</template>

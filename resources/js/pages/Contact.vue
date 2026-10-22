<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import ArrowIcon from '../components/ArrowIcon.vue';
import PageHeader from '../components/PageHeader.vue';
import SiteLayout from '../layouts/SiteLayout.vue';

defineOptions({ layout: SiteLayout });

const props = defineProps({
    content: { type: Object, required: true },
    areas: { type: Array, required: true },
    seo: { type: Object, required: true },
});

const page = usePage();
const clinic = computed(() => page.props.clinic);
const success = computed(() => page.props.flash?.success);

const form = useForm({
    name: '',
    phone: '',
    email: '',
    subject: '',
    message: '',
    consent: false,
    website: '', // honeypot, ukryte przed ludźmi
});

function submit() {
    form.post('/kontakt', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

const f = computed(() => props.content.form);
</script>

<template>
    <PageHeader :eyebrow="content.eyebrow" :title="content.title" :lead="content.lead" :crumbs="[{ name: 'Kontakt', url: '/kontakt' }]" />

    <div class="ddp-contact">
        <div>
            <p v-if="success" class="ddp-alert" role="status">{{ success }}</p>

            <form class="ddp-form" novalidate @submit.prevent="submit">
                <h2 class="ddp-h3">{{ f.title }}</h2>

                <div class="ddp-form__row ddp-form__row--2">
                    <div class="ddp-field" :class="{ 'ddp-field--error': form.errors.name }">
                        <label class="ddp-field__label" for="c-name">{{ f.name }}</label>
                        <input
                            id="c-name"
                            v-model="form.name"
                            class="ddp-input"
                            type="text"
                            autocomplete="given-name"
                            :aria-invalid="!!form.errors.name"
                            :aria-describedby="form.errors.name ? 'c-name-err' : null"
                        />
                        <p v-if="form.errors.name" id="c-name-err" class="ddp-field__msg">{{ form.errors.name }}</p>
                    </div>
                    <div class="ddp-field" :class="{ 'ddp-field--error': form.errors.phone }">
                        <label class="ddp-field__label" for="c-phone">{{ f.phone }}</label>
                        <input
                            id="c-phone"
                            v-model="form.phone"
                            class="ddp-input"
                            type="tel"
                            inputmode="tel"
                            autocomplete="tel"
                            :aria-invalid="!!form.errors.phone"
                            :aria-describedby="form.errors.phone ? 'c-phone-err' : null"
                        />
                        <p v-if="form.errors.phone" id="c-phone-err" class="ddp-field__msg">{{ form.errors.phone }}</p>
                    </div>
                </div>

                <div class="ddp-form__row ddp-form__row--2">
                    <div class="ddp-field" :class="{ 'ddp-field--error': form.errors.email }">
                        <label class="ddp-field__label" for="c-email">{{ f.email }}</label>
                        <input
                            id="c-email"
                            v-model="form.email"
                            class="ddp-input"
                            type="email"
                            inputmode="email"
                            autocomplete="email"
                            :aria-invalid="!!form.errors.email"
                            :aria-describedby="form.errors.email ? 'c-email-err' : null"
                        />
                        <p v-if="form.errors.email" id="c-email-err" class="ddp-field__msg">{{ form.errors.email }}</p>
                    </div>
                    <div class="ddp-field">
                        <label class="ddp-field__label" for="c-subject">{{ f.subject }}</label>
                        <select id="c-subject" v-model="form.subject" class="ddp-select">
                            <option value="">{{ f.subject_none }}</option>
                            <option v-for="area in areas" :key="area.slug" :value="area.name">{{ area.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="ddp-field" :class="{ 'ddp-field--error': form.errors.message }">
                    <label class="ddp-field__label" for="c-message">{{ f.message }}</label>
                    <textarea
                        id="c-message"
                        v-model="form.message"
                        class="ddp-textarea"
                        :placeholder="f.message_hint"
                        :aria-invalid="!!form.errors.message"
                        :aria-describedby="form.errors.message ? 'c-message-err' : null"
                    ></textarea>
                    <p v-if="form.errors.message" id="c-message-err" class="ddp-field__msg">{{ form.errors.message }}</p>
                </div>

                <div class="ddp-field" :class="{ 'ddp-field--error': form.errors.consent }">
                    <label class="ddp-check" for="c-consent">
                        <input id="c-consent" v-model="form.consent" type="checkbox" :aria-invalid="!!form.errors.consent" :aria-describedby="form.errors.consent ? 'c-consent-err' : null" />
                        <!-- Link otwiera politykę w nowej karcie, żeby nie zgubić wpisanych danych. Kliknięcie linku nie zaznacza pola. -->
                        <span>
                            {{ f.consent.before }}<a :href="clinic.legal.privacy" target="_blank" rel="noopener">{{ f.consent.link }}<span class="sr-only"> (otwiera się w nowej karcie)</span></a>{{ f.consent.after }}
                        </span>
                    </label>
                    <p v-if="form.errors.consent" id="c-consent-err" class="ddp-field__msg">{{ form.errors.consent }}</p>
                </div>

                <!-- Honeypot: ukryte pole pułapka, prawdziwe osoby go nie widzą i nie wypełniają. -->
                <div class="ddp-hp" aria-hidden="true">
                    <label for="c-website">Nie wypełniaj tego pola</label>
                    <input id="c-website" v-model="form.website" type="text" tabindex="-1" autocomplete="off" />
                </div>

                <button class="ddp-btn u-fill-sm" type="submit" :disabled="form.processing">
                    {{ form.processing ? f.sending : f.submit }}
                    <ArrowIcon />
                </button>
            </form>
        </div>

        <aside class="ddp-contact__info" aria-labelledby="info-title">
            <h2 id="info-title" class="ddp-h4">{{ content.info.title }}</h2>
            <address class="contact-lines">
                {{ clinic.name }}<br />
                ul. {{ clinic.address.street }}<br />
                {{ clinic.address.postal_code }} {{ clinic.address.city }}
            </address>
            <p class="contact-lines">
                <a :href="`tel:${clinic.phone_e164}`">{{ clinic.phone }}</a><br />
                <a :href="`mailto:${clinic.email}`">{{ clinic.email }}</a>
            </p>
            <a class="ddp-btn u-fill-sm" :href="clinic.booking_url" target="_blank" rel="noopener">
                {{ content.info.book }}<span class="sr-only"> (otwiera się w nowej karcie)</span>
                <ArrowIcon />
            </a>
            <a class="ddp-link" :href="clinic.maps_url" target="_blank" rel="noopener">
                <span class="ddp-link__text">{{ content.info.map }}</span>
                <ArrowIcon />
            </a>
        </aside>
    </div>
</template>

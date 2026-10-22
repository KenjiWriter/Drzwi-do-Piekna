<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Logo from './Logo.vue';

const page = usePage();
const clinic = computed(() => page.props.clinic);
const nav = computed(() => page.props.nav);
const year = new Date().getFullYear();
</script>

<template>
    <footer class="ddp-footer ddp-on-brand">
        <div class="ddp-footer__grid">
            <div>
                <Logo variant="on-brand" />
                <p class="ddp-text" style="margin-top: 24px; max-width: 30ch">
                    Naturalne efekty. Świadome decyzje. Indywidualne podejście.
                </p>
            </div>

            <nav aria-label="Strony">
                <h2 class="ddp-footer__h">Strony</h2>
                <ul class="ddp-footer__list">
                    <li v-for="item in nav" :key="item.href">
                        <Link class="ddp-footer__link" :href="item.href">{{ item.label }}</Link>
                    </li>
                </ul>
            </nav>

            <div>
                <h2 class="ddp-footer__h">Kontakt</h2>
                <address class="ddp-footer__addr">
                    ul. {{ clinic.address.street }}<br />
                    {{ clinic.address.postal_code }} {{ clinic.address.city }}
                </address>
                <ul class="ddp-footer__list" style="margin-top: 8px">
                    <li><a class="ddp-footer__link" :href="`tel:${clinic.phone_e164}`">{{ clinic.phone }}</a></li>
                    <li><a class="ddp-footer__link" :href="`mailto:${clinic.email}`">{{ clinic.email }}</a></li>
                </ul>
            </div>

            <div>
                <h2 class="ddp-footer__h">Znajdź nas</h2>
                <div class="ddp-footer__social">
                    <a class="ddp-footer__icon" :href="clinic.social.instagram" target="_blank" rel="noopener" aria-label="Instagram (otwiera się w nowej karcie)">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true" focusable="false">
                            <rect x="3" y="3" width="18" height="18" rx="5" />
                            <circle cx="12" cy="12" r="4" />
                            <circle cx="17.4" cy="6.6" r=".7" />
                        </svg>
                    </a>
                    <a class="ddp-footer__icon" :href="clinic.social.facebook" target="_blank" rel="noopener" aria-label="Facebook (otwiera się w nowej karcie)">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true" focusable="false">
                            <path d="M14.5 21v-8h2.7l.5-3.2h-3.2V7.8c0-.9.4-1.6 1.7-1.6H18V3.3C17.6 3.2 16.7 3 15.7 3 13.5 3 12 4.3 12 7.1v2.7H9.3V13H12v8z" />
                        </svg>
                    </a>
                </div>
                <ul class="ddp-footer__list" style="margin-top: 8px">
                    <li>
                        <a class="ddp-footer__link" :href="clinic.social.znanylekarz" target="_blank" rel="noopener">
                            ZnanyLekarz<span class="sr-only"> (otwiera się w nowej karcie)</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="ddp-footer__legal">
            <span>© {{ year }} {{ clinic.name }}</span>
            <span>
                <Link class="ddp-footer__link ddp-footer__link--inline" :href="clinic.legal.privacy">Polityka prywatności</Link>
                ·
                <Link class="ddp-footer__link ddp-footer__link--inline" :href="clinic.legal.cookies">Polityka cookies</Link>
            </span>
        </div>
    </footer>
</template>

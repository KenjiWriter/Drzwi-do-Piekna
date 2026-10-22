<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Logo from './Logo.vue';

const page = usePage();
const nav = computed(() => page.props.nav);
const clinic = computed(() => page.props.clinic);

// Bieżąca sekcja serwisu: /zabiegi/twarz podświetla też "Zabiegi".
function isCurrent(href) {
    const path = page.url.split(/[?#]/)[0];

    return path === href || path.startsWith(`${href}/`);
}

const open = ref(false);
const headerEl = ref(null);
const menuEl = ref(null);
const burgerEl = ref(null);

function close() {
    open.value = false;
}

function focusables() {
    const selector = 'a[href], button:not([disabled])';

    return [
        ...(headerEl.value?.querySelectorAll(selector) ?? []),
        ...(menuEl.value?.querySelectorAll(selector) ?? []),
    ];
}

// Esc zamyka menu, Tab krąży po nagłówku i menu (pułapka fokusu).
function onKeydown(event) {
    if (event.key === 'Escape') {
        close();
        burgerEl.value?.focus();
        return;
    }
    if (event.key !== 'Tab') return;

    const items = focusables();
    if (!items.length) return;

    const first = items[0];
    const last = items[items.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

watch(open, async (isOpen) => {
    document.documentElement.style.overflow = isOpen ? 'hidden' : '';

    if (isOpen) {
        document.addEventListener('keydown', onKeydown);
        await nextTick();
        menuEl.value?.querySelector('a')?.focus();
    } else {
        document.removeEventListener('keydown', onKeydown);
    }
});

let removeNavigateListener = () => {};

onMounted(() => {
    removeNavigateListener = router.on('navigate', close);
});

onBeforeUnmount(() => {
    removeNavigateListener();
    document.removeEventListener('keydown', onKeydown);
    document.documentElement.style.overflow = '';
});
</script>

<template>
    <header ref="headerEl" class="ddp-header" :class="{ 'ddp-header--open': open }">
        <Logo />

        <nav class="ddp-header__nav" aria-label="Główna nawigacja">
            <Link
                v-for="item in nav"
                :key="item.href"
                class="ddp-nav-link"
                :href="item.href"
                :aria-current="isCurrent(item.href) ? 'page' : null"
            >{{ item.label }}</Link>
        </nav>

        <a class="ddp-btn ddp-btn--outline ddp-header__cta" :href="clinic.booking_url" target="_blank" rel="noopener">
            Umów wizytę<span class="sr-only"> (otwiera się w nowej karcie)</span>
        </a>

        <button
            ref="burgerEl"
            class="ddp-burger"
            type="button"
            :aria-label="open ? 'Zamknij menu' : 'Otwórz menu'"
            :aria-expanded="open"
            aria-controls="mobile-menu"
            @click="open = !open"
        >
            <span></span><span></span><span></span>
        </button>
    </header>

    <div v-if="open" id="mobile-menu" ref="menuEl" class="ddp-menu" role="dialog" aria-modal="true" aria-label="Menu">
        <nav aria-label="Menu mobilne">
            <Link v-for="item in nav" :key="item.href" class="ddp-menu__link" :href="item.href" @click="close">{{ item.label }}</Link>
        </nav>
        <div class="ddp-menu__foot">
            <p class="ddp-menu__contact">
                ul. {{ clinic.address.street }}, {{ clinic.address.city }}<br />
                {{ clinic.phone }}<br />
                {{ clinic.email }}
            </p>
            <a class="ddp-btn ddp-btn--block" :href="clinic.booking_url" target="_blank" rel="noopener">
                Umów wizytę<span class="sr-only"> (otwiera się w nowej karcie)</span>
            </a>
            <a class="ddp-btn ddp-btn--outline ddp-btn--block" :href="`tel:${clinic.phone_e164}`">Zadzwoń</a>
        </div>
    </div>
</template>

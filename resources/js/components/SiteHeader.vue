<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import A11yMenu from './A11yMenu.vue';
import A11yOptions from './A11yOptions.vue';
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

/*
 * Na komputerze menu zwija się do burgera (ddp-header--m), gdy nie mieści się w jednym wierszu: przy powiększonym
 * tekście (A+, A++) i przy oknie tuż powyżej 1024 px. Mierzymy bez klasy i od razu ją przywracamy, w jednej klatce,
 * więc nic nie mruga. Na telefonie burger jest zawsze, więc klasa nie ma znaczenia.
 */
const compact = ref(false);
const MIN_SPACE = 32; // najmniejszy odstęp między logo, menu i przyciskami, żeby nagłówek nie wyglądał na ściśnięty
let frame = 0;

function measure() {
    const el = headerEl.value;
    if (!el) return;

    el.classList.remove('ddp-header--m');

    let overflows = false;
    if (window.innerWidth >= 1024) {
        const style = getComputedStyle(el);
        const available = el.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight);
        const items = [...el.children].filter((child) => child.offsetWidth > 0);
        const needed = items.reduce((sum, child) => sum + child.getBoundingClientRect().width, 0) + MIN_SPACE * (items.length - 1);
        overflows = needed > available;
    }

    el.classList.toggle('ddp-header--m', overflows);
    compact.value = overflows;
}

function scheduleMeasure() {
    cancelAnimationFrame(frame);
    frame = requestAnimationFrame(measure);
}

let removeNavigateListener = () => {};

onMounted(() => {
    removeNavigateListener = router.on('navigate', close);
    measure();
    document.fonts?.ready.then(measure);
    window.addEventListener('resize', scheduleMeasure);
    window.addEventListener('ddp:a11y', measure);
});

onBeforeUnmount(() => {
    removeNavigateListener();
    cancelAnimationFrame(frame);
    window.removeEventListener('resize', scheduleMeasure);
    window.removeEventListener('ddp:a11y', measure);
    document.removeEventListener('keydown', onKeydown);
    document.documentElement.style.overflow = '';
});
</script>

<template>
    <header ref="headerEl" class="ddp-header" :class="{ 'ddp-header--open': open, 'ddp-header--m': compact }">
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

        <div class="ddp-header__end">
            <a class="ddp-btn ddp-btn--outline ddp-header__cta" :href="clinic.booking_url" target="_blank" rel="noopener">
                Umów wizytę<span class="sr-only"> (otwiera się w nowej karcie)</span>
            </a>

            <A11yMenu />

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
        </div>
    </header>

    <div v-if="open" id="mobile-menu" ref="menuEl" class="ddp-menu" role="dialog" aria-modal="true" aria-label="Menu">
        <nav aria-label="Menu mobilne">
            <Link v-for="item in nav" :key="item.href" class="ddp-menu__link" :href="item.href" @click="close">{{ item.label }}</Link>
        </nav>
        <!-- Na najwęższych ekranach ikona dostępności nie mieści się w nagłówku, więc opcje są tutaj (CSS pokazuje je tylko wtedy). -->
        <div class="ddp-menu__a11y">
            <A11yOptions />
        </div>
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

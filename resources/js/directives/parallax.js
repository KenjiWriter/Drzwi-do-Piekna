/**
 * v-parallax: zdjęcie w kontenerze przesuwa się wolniej niż strona, więc przy przewijaniu sprawia wrażenie głębi.
 *
 * Dyrektywa tylko liczy przesunięcie i zapisuje je jako --parallax-y na elemencie. Samo przesunięcie robi CSS
 * (transform na <picture> w .ddp-hero__media), czyli bez przeliczania układu. Pracuje, gdy element jest widoczny,
 * i aktualizuje się raz na klatkę animacji. Przy ograniczonym ruchu w systemie nic nie robi (CSS wtedy też wyłącza
 * zapas obrazu), a przed startem JS zdjęcie stoi w pozycji początkowej, więc SSR nie powoduje przeskoku.
 *
 * Użycie: v-parallax albo v-parallax="{ range: 0.12 }", gdzie range to część wysokości kontenera, o jaką zdjęcie
 * przesunie się po całkowitym przewinięciu (musi odpowiadać zapasowi `inset` w CSS).
 */
const DEFAULT_RANGE = 0.12;

export default {
    mounted(el, binding) {
        if (typeof IntersectionObserver === 'undefined') return;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const range = binding.value?.range ?? DEFAULT_RANGE;
        let frame = 0;

        const update = () => {
            frame = 0;
            const { top, height } = el.getBoundingClientRect();
            // 0, gdy kontener jest na swoim miejscu, do 1, gdy przewinięto go w całości nad górną krawędź ekranu.
            const progress = Math.min(Math.max(-top / height, 0), 1);

            el.style.setProperty('--parallax-y', `${(progress * range * height).toFixed(1)}px`);
        };

        const schedule = () => {
            if (!frame) frame = requestAnimationFrame(update);
        };

        const start = () => {
            window.addEventListener('scroll', schedule, { passive: true });
            window.addEventListener('resize', schedule, { passive: true });
            update();
        };

        const stop = () => {
            window.removeEventListener('scroll', schedule);
            window.removeEventListener('resize', schedule);
            if (frame) cancelAnimationFrame(frame);
            frame = 0;
        };

        const observer = new IntersectionObserver(([entry]) => (entry.isIntersecting ? start() : stop()));
        observer.observe(el);

        el._parallaxCleanup = () => {
            observer.disconnect();
            stop();
        };
    },

    unmounted(el) {
        el._parallaxCleanup?.();
    },
};

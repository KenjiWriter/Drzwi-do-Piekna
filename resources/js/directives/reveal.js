/**
 * v-reveal: sekcja poniżej pierwszego ekranu pojawia się łagodnie (opacity + 14 px).
 * Treść jest w HTML od początku (SSR); ukrywamy ją dopiero po starcie JS i tylko
 * wtedy, gdy leży poza widokiem. Przy ograniczonym ruchu nic nie robimy.
 */
export default {
    mounted(el) {
        if (typeof IntersectionObserver === 'undefined') return;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        if (el.getBoundingClientRect().top < window.innerHeight * 0.92) return;

        document.documentElement.classList.add('js');
        el.classList.add('ddp-reveal');

        const observer = new IntersectionObserver(([entry]) => {
            if (!entry.isIntersecting) return;
            el.classList.add('is-in');
            observer.disconnect();
        }, { threshold: 0.12 });

        observer.observe(el);
    },
};

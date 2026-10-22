import { createInertiaApp } from '@inertiajs/vue3';
import parallax from './directives/parallax';
import reveal from './directives/reveal';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.vue');

        return pages[`./pages/${name}.vue`]();
    },
    // Inertia 3 tworzy aplikację sama (klient i SSR); tu tylko rejestrujemy dyrektywy.
    withApp(app) {
        app.directive('reveal', reveal);
        app.directive('parallax', parallax);
    },
});

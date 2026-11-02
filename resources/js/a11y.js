/*
 * Ustawienia dostępności odwiedzającej (WCAG 2.1): wielkość tekstu i wersja kontrastowa.
 *
 * Stan żyje na <html>: data-text-size="lg" | "xl" (brak atrybutu to tekst standardowy) i data-contrast.
 * Wybór zapisujemy w localStorage pod kluczem 'ddp-a11y' (opisany w polityce prywatności), a skrypt w <head>
 * (resources/views/app.blade.php) odtwarza go przed pierwszym malowaniem, więc strona nie mruga przy wejściu.
 */
export const STORAGE_KEY = 'ddp-a11y';

export const TEXT_SIZES = ['md', 'lg', 'xl'];

const CONTRAST_THEME_COLOR = '#000000';

/** Ustawienia odczytane z <html> (tam zapisał je skrypt w <head>). */
export function currentPrefs() {
    const root = document.documentElement;
    const text = root.getAttribute('data-text-size');

    return {
        text: TEXT_SIZES.includes(text) ? text : 'md',
        contrast: root.hasAttribute('data-contrast'),
    };
}

/** Stosuje ustawienia, zapamiętuje je (domyślne czyszczą wpis) i daje znać nagłówkowi, żeby przeliczył miejsce. */
export function applyPrefs({ text, contrast }) {
    const root = document.documentElement;

    if (text === 'lg' || text === 'xl') {
        root.setAttribute('data-text-size', text);
    } else {
        root.removeAttribute('data-text-size');
    }
    root.toggleAttribute('data-contrast', Boolean(contrast));

    // Kolor paska przeglądarki na telefonie. Pierwotny kolor z szablonu trzymamy w data-base.
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) {
        if (!meta.hasAttribute('data-base')) meta.setAttribute('data-base', meta.getAttribute('content'));
        meta.setAttribute('content', contrast ? CONTRAST_THEME_COLOR : meta.getAttribute('data-base'));
    }

    try {
        if ((text === 'lg' || text === 'xl') || contrast) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({ text, contrast: Boolean(contrast) }));
        } else {
            localStorage.removeItem(STORAGE_KEY);
        }
    } catch {
        // Przeglądarka bez dostępu do localStorage (tryb prywatny, blokada): ustawienie działa do przeładowania.
    }

    window.dispatchEvent(new CustomEvent('ddp:a11y'));
}

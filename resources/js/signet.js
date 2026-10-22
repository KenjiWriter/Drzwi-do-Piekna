/*
 * Geometria znaku (sygnetu) Drzwi do Piękna. Jedyne źródło: korzystają z niej Signet.vue, Intro.vue i scripts/mail-assets.mjs.
 *
 * Znak jest odtworzony z wizualizacji zatwierdzonej przez klienta (storyboard animacji logo) i nałożony na nią punkt po punkcie:
 * szlif szmaragdowy (ośmiokąt), w środku podwójna ramka drzwi (ramka i drzwi), a między nimi szesnaście przekątnych, które
 * z każdego narożnika ramki biegną do czterech narożników szlifu: dwóch sąsiednich (krótkie) i dwóch przeciwległych (długie,
 * krzyżują się w kształt X po bokach oraz nad i pod drzwiami). Drzwi leżą odrobinę niżej niż środek szlifu, tak jak u klienta.
 * Nie wygładzaj proporcji ani nie symetryzuj pionowo: znak ma być identyczny z wizualizacją.
 * Współrzędne w układzie 120 x 150 (viewBox), znak zajmuje ok. 107 x 138.
 */
export const SIGNET_VIEW_BOX = '0 0 120 150';

const P = {
    T1: [22.3, 6.2], T2: [97.7, 6.2], R1: [113.5, 23.2], R2: [113.5, 127.3],
    B2: [97.7, 143.8], B1: [22.3, 143.8], L2: [6.5, 127.3], L1: [6.5, 23.2],
    FTL: [34.5, 38.5], FTR: [85.5, 38.5], FBR: [85.5, 116.1], FBL: [34.5, 116.1],
};

const at = (name) => P[name].join(' ');
const segment = (from, to) => `M${at(from)}L${at(to)}`;

/** Obrys szlifu (ośmiokąt). */
export const outline = 'M22.3 6.2H97.7L113.5 23.2V127.3L97.7 143.8H22.3L6.5 127.3V23.2Z';
/** Zewnętrzna ramka drzwi. */
export const frame = 'M34.5 38.5H85.5V116.1H34.5Z';
/** Drzwi (wewnętrzny prostokąt). */
export const door = 'M38.5 43.5H81.5V111.5H38.5Z';

/** Krótkie przekątne: z narożnika ramki do dwóch najbliższych narożników szlifu. */
export const near = [
    segment('FTL', 'T1'), segment('FTL', 'L1'),
    segment('FTR', 'T2'), segment('FTR', 'R1'),
    segment('FBL', 'B1'), segment('FBL', 'L2'),
    segment('FBR', 'B2'), segment('FBR', 'R2'),
];

/** Długie przekątne: z narożnika ramki do dwóch przeciwległych narożników szlifu (krzyżują się w X). */
export const far = [
    segment('FTL', 'T2'), segment('FTL', 'L2'),
    segment('FTR', 'T1'), segment('FTR', 'R2'),
    segment('FBL', 'B2'), segment('FBL', 'L1'),
    segment('FBR', 'B1'), segment('FBR', 'R1'),
];

/** Pięć ścieżek do statycznego znaku: obrys, ramka, drzwi, krótkie i długie przekątne. */
export const signetPaths = [outline, frame, door, near.join(''), far.join('')];

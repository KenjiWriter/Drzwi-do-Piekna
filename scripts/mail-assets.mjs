// Obrazki do e-maili. Większość klientów poczty nie wyświetla SVG, więc znak marki (geometria z resources/js/signet.js)
// renderujemy do PNG w podwójnej gęstości (wyświetlany 48 x 60 px).
// Wynik jest w repozytorium (resources/mail/signet.png), a skrypt uruchamiamy tylko po zmianie znaku: npm run mail-assets
import { mkdirSync } from 'node:fs';
import sharp from 'sharp';
import { SIGNET_VIEW_BOX, signetPaths } from '../resources/js/signet.js';

const BRAND = '#5C1F2E';

const paths = signetPaths.map((d) => `<path d="${d}"/>`).join('');

const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${SIGNET_VIEW_BOX}" width="96" height="120">
  <g fill="none" stroke="${BRAND}" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round">${paths}</g>
</svg>`;

mkdirSync('resources/mail', { recursive: true });
await sharp(Buffer.from(svg), { density: 288 }).resize(96, 120).png({ compressionLevel: 9 }).toFile('resources/mail/signet.png');

console.log('resources/mail/signet.png');

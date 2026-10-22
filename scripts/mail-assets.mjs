// Obrazki do e-maili. Większość klientów poczty nie wyświetla SVG, więc znak marki (ta sama geometria co
// resources/js/components/Signet.vue) renderujemy do PNG w podwójnej gęstości (wyświetlany 48 x 60 px).
// Wynik jest w repozytorium (resources/mail/signet.png), a skrypt uruchamiamy tylko po zmianie znaku: npm run mail-assets
import { mkdirSync } from 'node:fs';
import sharp from 'sharp';

const BRAND = '#5C1F2E';

const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 150" width="96" height="120">
  <g fill="none" stroke="${BRAND}" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round">
    <path d="M30 6H90L114 30V120L90 144H30L6 120V30Z"/>
    <path d="M42 28H78L92 42V108L78 122H42L28 108V42Z"/>
    <path d="M46 48H74V102H46Z"/>
    <path d="M46 48L42 28M46 48L28 42M74 48L78 28M74 48L92 42M74 102L78 122M74 102L92 108M46 102L42 122M46 102L28 108"/>
    <path d="M42 28L30 6M78 28L90 6M92 42L114 30M92 108L114 120M78 122L90 144M42 122L30 144M28 108L6 120M28 42L6 30"/>
  </g>
</svg>`;

mkdirSync('resources/mail', { recursive: true });
await sharp(Buffer.from(svg), { density: 288 }).resize(96, 120).png({ compressionLevel: 9 }).toFile('resources/mail/signet.png');

console.log('resources/mail/signet.png');

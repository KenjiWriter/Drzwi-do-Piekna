/**
 * Płyty tła animacji powitalnej (scena WebGL: resources/js/intro-light.js): zdjęcia gładkiej ściany z miękkim światłem okna i cieniem
 * liści. Źródła (resources/images/source/intro) wygenerowano w Artlist (Nano Banana 2, 2K, 16:9) z opisów w stylu storyboardu klienta:
 * ciepły, beżowo-szary tynk, ukośna tafla światła i rozmyte cienie liści, bez widocznych roślin, okien i przedmiotów.
 *
 *   npm run intro-plates
 *
 * Każda płyta trafia do public/intro/plate-N.webp jako obraz w skali szarości (sama jasność, ok. 100 do 200 KB): shader zamienia go
 * na paletę marki, a kolejność plików to numer płyty (data-intro-plate), więc nie zmieniaj jej bez zmiany tabeli PLATES w intro-light.js.
 * Liczba płyt jest też w config/clinic.php (intro_plates), a test pilnuje, żeby pliki istniały.
 */
import fs from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';

const SRC = 'resources/images/source/intro';
const OUT = 'public/intro';
const WIDTH = 1600;

// crop: ułamek wysokości odcinany u góry (płyta „paproć” ma tam widoczną, żywą roślinę, której na ścianie być nie może).
const plates = [
    { file: 'olive.jpg' },
    { file: 'window.jpg' },
    { file: 'monstera.jpg' },
    { file: 'fern.jpg', crop: 0.2 },
];

fs.mkdirSync(OUT, { recursive: true });
for (const f of fs.readdirSync(OUT)) if (/^plate-\d+\.webp$/.test(f)) fs.rmSync(path.join(OUT, f));

for (const [index, { file, crop = 0 }] of plates.entries()) {
    let image = sharp(path.join(SRC, file));
    if (crop > 0) {
        const { width, height } = await image.metadata();
        const top = Math.round(height * crop);
        image = sharp(await image.extract({ left: 0, top, width, height: height - top }).toBuffer());
    }

    const out = path.join(OUT, `plate-${index}.webp`);
    // Rozciągnięcie histogramu (1. i 99. percentyl), żeby wszystkie płyty miały ten sam zakres jasności, a shader nie musiał ich dostrajać.
    await image
        .resize({ width: WIDTH })
        .grayscale()
        .normalise({ lower: 1, upper: 99 })
        .webp({ quality: 78, effort: 5 })
        .toFile(out);

    const { width, height, size } = await sharp(out).metadata().then((m) => ({ ...m, size: fs.statSync(out).size }));
    console.log(`${out}: ${width}x${height}, ${Math.round(size / 1024)} KB`);
}

/**
 * Potok obrazów: kadruje zdjęcia źródłowe (resources/images/source), tworzy warianty
 * responsywne WebP w public/images i manifest resources/js/images.json
 * (src, srcset, wymiary, kolor dominujący jako tło na czas ładowania).
 *
 *   npm run images
 *
 * Zdjęcia są tymczasowe (demo, Pexels). Docelowo podmieniamy pliki źródłowe zdjęciami klientki.
 */
import fs from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';

const SRC = 'resources/images/source';
const OUT = 'public/images';
const MANIFEST = 'resources/js/images.json';

// aspect: [szerokość, wysokość] kadru; extract: ręczny wycinek źródła; before: wariant "przed" do podglądu układu.
const images = {
    hero: { file: 'pexels-8058449.jpg', widths: [960, 1440, 1920], alt: 'Makro oka kobiety w ciepłym świetle z cieniem żaluzji' },
    'hero-m': { file: 'pexels-8058449.jpg', extract: { left: 640, top: 0, width: 900, height: 1125 }, widths: [480, 800, 1100], alt: 'Makro oka kobiety w ciepłym świetle z cieniem żaluzji' },
    interior: { file: 'pexels-17640379.jpg', extract: { left: 880, top: 0, width: 1120, height: 1414 }, widths: [640, 960, 1120], alt: 'Jasne wnętrze gabinetu z kamienną recepcją i dużymi oknami' },
    'tile-face': { file: 'pexels-16157045.jpg', aspect: [4, 5], widths: [480, 720, 960], alt: 'Twarz: makro oka w ciepłym świetle' },
    'tile-lips': { file: 'pexels-8183931.jpg', aspect: [4, 5], widths: [480, 720, 960], alt: 'Usta: naturalne, zbliżenie' },
    'tile-neck': { file: 'pexels-20783742.jpg', aspect: [4, 5], widths: [480, 720, 960], alt: 'Szyja i dekolt: miękkie światło' },
    'tile-skin': { file: 'pexels-8624595.jpg', aspect: [4, 5], widths: [480, 720, 960], alt: 'Jakość skóry: zbliżenie na obojczyk i piegi' },
    plant: { file: 'pexels-27359227.jpg', aspect: [5, 4], widths: [640, 960, 1280], alt: 'Trawa pampasowa rzucająca cień na jasną ścianę' },
    vase: { file: 'pexels-30162927.jpg', aspect: [4, 5], widths: [640, 960, 1280], alt: 'Wazon z suszonymi kwiatami i cieniem w ciepłym świetle' },
    philo: { file: 'pexels-8626069.jpg', widths: [800, 1280, 1600], alt: 'Zbliżenie na szyję i obojczyk dojrzałej kobiety' },
    'ba-1': { file: 'pexels-8626076.jpg', aspect: [3, 4], widths: [320, 480, 720], alt: 'Skóra szyi', before: true },
    'ba-2': { file: 'pexels-14153822.jpg', aspect: [3, 4], widths: [320, 480, 720], alt: 'Skóra okolic ust', before: true },
    'ba-3': { file: 'pexels-8183931.jpg', aspect: [3, 4], widths: [320, 480, 720], alt: 'Skóra twarzy', before: true },
};

fs.mkdirSync(OUT, { recursive: true });
for (const f of fs.readdirSync(OUT)) fs.rmSync(path.join(OUT, f));

const manifest = {};

async function render(key, cfg, variant) {
    const meta = await sharp(path.join(SRC, cfg.file)).metadata();
    const base = cfg.extract ?? { left: 0, top: 0, width: meta.width, height: meta.height };
    const ratio = cfg.aspect ? cfg.aspect[0] / cfg.aspect[1] : base.width / base.height;
    const name = variant ? `${key}-${variant}` : key;
    const entries = [];

    for (const w of cfg.widths) {
        const h = Math.round(w / ratio);
        let img = sharp(path.join(SRC, cfg.file)).extract(base);

        if (cfg.aspect) {
            img = img.resize({ width: w, height: h, fit: 'cover', position: sharp.strategy.attention });
        } else {
            img = img.resize({ width: w });
        }
        if (variant === 'before') {
            img = img.modulate({ saturation: 0.72, brightness: 0.96 }).linear(0.9, 12);
        }

        const file = `${name}-${w}.webp`;
        await img.webp({ quality: 76, effort: 5 }).toFile(path.join(OUT, file));
        entries.push({ w, h, file });
    }

    const stats = await sharp(path.join(OUT, entries[0].file)).stats();
    const { r, g, b } = stats.dominant;
    const mid = entries[Math.floor(entries.length / 2)];

    manifest[name] = {
        src: `/images/${mid.file}`,
        srcset: entries.map((e) => `/images/${e.file} ${e.w}w`).join(', '),
        width: mid.w,
        height: mid.h,
        color: `rgb(${r}, ${g}, ${b})`,
        alt: cfg.alt,
    };
}

for (const [key, cfg] of Object.entries(images)) {
    await render(key, cfg);
    if (cfg.before) await render(key, cfg, 'before');
}

fs.writeFileSync(MANIFEST, JSON.stringify(manifest, null, 4) + '\n');
const total = fs.readdirSync(OUT).reduce((sum, f) => sum + fs.statSync(path.join(OUT, f)).size, 0);
console.log(`${Object.keys(manifest).length} obrazów, ${fs.readdirSync(OUT).length} plików, ${(total / 1024 / 1024).toFixed(1)} MB`);

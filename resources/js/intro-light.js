/*
 * Animacja powitalna w 3D: lekki silnik na WebGL (dwa shadery, bez bibliotek; Three.js nie jest potrzebny).
 *
 * Trzy plany (decyzja z 2026-10-23: światło i znak mają być przestrzenne, nie płaskie):
 *   1. Tło: ściana liczona w shaderze na piksel. Światło rozproszone z ciemniejszymi brzegami, szeroka ukośna tafla słońca
 *      wpadającego przez okno z miękkim półcieniem, która wędruje przez planszę od lewej do prawej przez czas rysowania znaku,
 *      cień rośliny (tekstura z tych samych ścieżek liści co w Intro.vue, rozmywana w shaderze, widoczny głównie w słońcu,
 *      kołysze się i przesuwa przeciwnie do światła), poświata za znakiem i ziarno przeciw pasom w gradientach.
 *   2. Między tłem a znakiem: letnia bryza, czyli szeroki, rozmyty półokrąg światła, który płynie od lewej do prawej jak podmuch
 *      wiatru (lekko faluje w pionie) i leży na własnej głębokości: przy ruchu kamery przesuwa się inaczej niż ściana i znak
 *      (paralaksa), więc widać, że przechodzi między nimi.
 *   3. Z przodu: sygnet jako bryła i napisy (napisy zostają w HTML, Intro.vue). Stół (drzwi) najbliżej widza, ramka w środku, obrys
 *      szlifu najgłębiej, a szesnaście przekątnych łączy te plany jak krawędzie fasetek. Widziany na wprost ma dokładnie proporcje
 *      wizualizacji klienta (współrzędne planów są skalowane tak, by rzut perspektywiczny zgadzał się z geometrią 2D z signet.js).
 *      Kamera zaczyna lekko z boku i z góry i ustawia się na wprost w czasie rysowania, więc widać głębię, a końcowa klatka jest
 *      identyczna ze znakiem 2D. Tafla słońca jest pochylona także w głąb: przechodząc przez znak rozświetla najpierw plany
 *      bliższe widza, potem dalsze (krawędzie jaśnieją i dostają miękką poświatę). Znak NIE rzuca cienia na ścianę: cień rozmywał
 *      linie i znak był niewyraźny (decyzja użytkownika z 2026-10-23), a znak ma być zawsze ostry. Linie rysują się w tej kolejności i czasie co w storyboardzie (dane z Intro.vue), z jasnym punktem
 *      na końcu rysowanej linii.
 * Bez gwiazdek, cząsteczek i ozdobników: tylko światło, cień rośliny i bryła. Przy ograniczonym ruchu jedna nieruchoma klatka (znak
 * narysowany, kamera na wprost). Bez WebGL Intro.vue zostaje przy wersji CSS/SVG.
 *
 * Układ światła zależy od ziarna (seed), więc przy każdym odtworzeniu jest inny (patrz lightFromSeed): skrypt w <head> losuje ziarno
 * i zapisuje je w html[data-intro-seed], a ?intro=N wymusza ziarno N (podgląd i testy). Nieparzyste ziarno odbija całą scenę w poziomie
 * (słońce wpada z prawej, to samo dotyczy cienia rośliny, bryzy i kamery), a reszta zmienia kąt tafli, tor jej ruchu (lekko w górę
 * albo w dół), położenie, szerokość i stronę drugiej tafli oraz to, czy cień rośliny pada u góry, czy u dołu.
 *
 * Każde skrzydło drzwi ma własne płótno, które pokazuje swoją połowę tej samej sceny (u_origin / u_remap), więc obraz rozchyla się
 * razem z drzwiami. Płótna renderują w pełnej rozdzielczości urządzenia (do 1,5 x; na bardzo dużych ekranach do 0,75), bo kreski znaku
 * muszą być ostre.
 */

// Wspólny fragment GLSL: położenie tafli słońca w czasie. q to współrzędne ekranu (środek w (0,0), jednostka = wysokość sceny,
// y w dół), k skaluje smugę na wąskich ekranach, z to głębokość punktu (jednostki znaku, dodatnia bliżej widza).
const SUN_GLSL = `
float ease(float x) { x = clamp(x, 0.0, 1.0); return x * x * (3.0 - 2.0 * x); }
// Układ światła z ziarna (lightFromSeed). Wszystko liczymy w układzie, w którym słońce wpada z lewej; przy nieparzystym ziarnie scenę
// odbijamy w poziomie przed obliczeniem (u_look.x = -1), więc światło wpada z prawej.
uniform vec4 u_sun;    // (kierunek ruchu tafli x, y, oś tafli x, y)
uniform vec4 u_shape;  // (środek toru x, y, szerokość tafli, odstęp drugiej tafli ze znakiem)
uniform vec2 u_look;   // (-1 lub 1: odbicie w poziomie, 0 lub 1: cień rośliny odwrócony w pionie)
vec2 sunDir() { return u_sun.zw; }
float sunTravel(float t) { return ease(t / 3.0) + 0.02 * max(t - 3.0, 0.0); }
vec2 sunCenter(float t, float k) {
    vec2 c = u_shape.xy + u_sun.xy * (1.08 * (sunTravel(t) - 0.5) + 0.012 * sin(t * 0.5));
    return vec2(c.x * k, c.y);
}
// Odległość od osi tafli (w poprzek), z pochyleniem w głąb: punkty bliżej widza (z > 0) światło mija wcześniej.
float sunS(vec2 q, float t, float k, float z) {
    vec2 dir = sunDir();
    vec2 perp = vec2(dir.y, -dir.x);
    return dot(q - sunCenter(t, k), perp) - z * 0.0024;
}
float sunAlong(vec2 q, float t, float k) { return dot(q - sunCenter(t, k), sunDir()); }
float sunBreath(float t) { return 1.0 + 0.045 * sin(t * 0.7) + 0.03 * sin(t * 1.9 + 1.3); }
`;

const WALL_VERTEX = 'attribute vec2 a;void main(){gl_Position=vec4(a,0.,1.);}';

const WALL_FRAGMENT = `
precision mediump float;

uniform vec2 u_res;        // rozmiar płótna w pikselach
uniform vec2 u_origin;     // położenie płótna w scenie (piksele, y w dół)
uniform vec2 u_scene;      // rozmiar całej sceny (piksele)
uniform float u_time;      // sekundy od startu animacji
uniform float u_motion;    // 1 ruch, 0 nieruchoma klatka (ograniczony ruch)
uniform vec2 u_breeze;     // paralaksa planu bryzy (jednostki q) przy ruchu kamery
uniform sampler2D u_plant; // cień rośliny (alfa), w układzie całej sceny

// Kolory ściany: w pełnym świetle i w cieniu (cień odrobinę chłodniejszy, fiołkowy jak na zdjęciach storyboardu).
const vec3 LIT = vec3(0.965, 0.922, 0.875);
const vec3 DARK = vec3(0.690, 0.600, 0.525);
const vec3 WARM = vec3(1.0, 0.975, 0.935);

${SUN_GLSL}

float hash(vec2 p) { return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453); }

// Rozmyty cień rośliny: 12 próbek na dysku Poissona, promień w ułamku wysokości sceny.
float shadow(vec2 uv, float r) {
    vec2 k = vec2(r * u_scene.y / u_scene.x, r);
    float s = 0.0;
    s += texture2D(u_plant, uv + k * vec2(-0.326, -0.406)).a;
    s += texture2D(u_plant, uv + k * vec2(-0.840, -0.074)).a;
    s += texture2D(u_plant, uv + k * vec2(-0.696,  0.457)).a;
    s += texture2D(u_plant, uv + k * vec2(-0.203,  0.621)).a;
    s += texture2D(u_plant, uv + k * vec2( 0.962, -0.195)).a;
    s += texture2D(u_plant, uv + k * vec2( 0.473, -0.480)).a;
    s += texture2D(u_plant, uv + k * vec2( 0.519,  0.767)).a;
    s += texture2D(u_plant, uv + k * vec2( 0.185, -0.893)).a;
    s += texture2D(u_plant, uv + k * vec2( 0.507,  0.064)).a;
    s += texture2D(u_plant, uv + k * vec2( 0.896,  0.412)).a;
    s += texture2D(u_plant, uv + k * vec2(-0.322, -0.933)).a;
    s += texture2D(u_plant, uv + k * vec2(-0.792, -0.598)).a;
    return s / 12.0;
}

void main() {
    vec2 scenePx = vec2(gl_FragCoord.x, u_res.y - gl_FragCoord.y) + u_origin;
    vec2 uv = scenePx / u_scene;                       // 0..1, y w dół
    float aspect = u_scene.x / u_scene.y;
    vec2 q = (uv - 0.5) * vec2(aspect, 1.0);           // środek sceny w (0,0), jednostka = wysokość sceny
    float t = u_motion > 0.5 ? u_time : 3.0;           // bez ruchu: klatka po przejściu bryzy
    float k = clamp(aspect / 1.5, 0.5, 1.0);           // na wąskim ekranie (pion) smuga jest węższa i bliżej środka

    // Układ, w którym słońce wpada z lewego górnego rogu; przy nieparzystym ziarnie scena jest odbita w poziomie (światło z prawej).
    float m = u_look.x;
    vec2 qm = vec2(q.x * m, q.y);
    vec2 uvm = vec2(m > 0.0 ? uv.x : 1.0 - uv.x, uv.y);

    // 1. Światło rozproszone: ciemniejsze brzegi (zwłaszcza dolny róg po stronie przeciwnej do słońca) i ciepła plama okna w rogu.
    float edge = length((qm - vec2(-0.12 * k, -0.08)) * vec2(0.82, 1.0));
    float ambient = 0.40 - 0.26 * smoothstep(0.30, 1.15, edge);
    vec2 corner = uvm * vec2(aspect, 1.0);
    ambient += 0.20 * exp(-dot(corner, corner) * 1.6);

    // 2. Słońce przez okno: szeroki ukośny pas z miękkim półcieniem (kąt, tor i strona z ziarna), wędruje przez planszę, domyślnie od lewej do
    //    prawej przez czas rysowania znaku (ok. 3 s, jak w kolejnych klatkach storyboardu), przechodzi przez środek około 1,4 s,
    //    potem powoli dryfuje dalej i lekko „oddycha”.
    float s = sunS(qm, t, k, 0.0);
    float along = sunAlong(qm, t, k);
    float width = k * u_shape.z;
    float band = smoothstep(0.42 * width, 0.22 * width, abs(s));
    float band2 = 0.40 * smoothstep(0.26 * width, 0.10 * width, abs(s - u_shape.w * k));
    float fall = 1.0 - 0.45 * smoothstep(-0.55, 0.95, along);
    float sun = 0.68 * (band + band2) * fall * sunBreath(t);

    // 3. Cień rośliny: przesuwa się przeciwnie do wędrującego światła (paralaksa), kołysze się i delikatnie faluje na wietrze;
    //    widoczny głównie w słońcu.
    float travel = sunTravel(t);
    float sway = sin(t * 0.45 - 1.2);
    vec2 uvS = uvm + vec2(-0.014 * sway, 0.009 * sway) * u_motion + 0.035 * u_sun.xy * (travel - 0.5) * u_motion;
    uvS += 0.0035 * vec2(sin(uvm.y * 9.0 + t * 0.8), cos(uvm.x * 7.0 + t * 0.6)) * u_motion;
    uvS.y = mix(uvS.y, 1.0 - uvS.y, u_look.y);
    float sh = shadow(uvS, 0.009);
    float lit = ambient * (1.0 - 0.36 * sh) + sun * (1.0 - 0.72 * sh);

    vec3 col = mix(DARK, LIT, clamp(lit, 0.0, 1.0));
    col += vec3(0.030, 0.012, -0.015) * sun;             // słońce jest cieplejsze niż światło rozproszone

    // 4. Letnia bryza (plan między ścianą a znakiem): szeroki, rozmyty półokrąg światła płynący od lewej do prawej przez środek
    //    znaku (ok. 0,25 do 2,9 s), lekko falujący w pionie jak podmuch wiatru, z własną paralaksą względem kamery (u_breeze).
    float p = ease((t - 0.25) / 2.65);
    float bx = mix(-0.75 * aspect - 0.2, 0.75 * aspect + 0.2, p) + u_breeze.x * m;
    float by = 0.05 * sin(t * 1.3 + 0.6) * u_motion + u_breeze.y + 1.1 * u_sun.y * (p - 0.5);
    float R = max(0.62, 0.5 * aspect);
    float d = length(qm - vec2(bx, by));
    float blob = pow(1.0 - smoothstep(0.0, R, d), 1.6);
    float tail = smoothstep(-0.55 * R, 0.12 * R, qm.x - bx);
    float env = smoothstep(0.0, 0.14, p) * (1.0 - smoothstep(0.8, 1.0, p)) * u_motion;
    col = mix(col, WARM, 0.32 * blob * tail * env * (1.0 - 0.3 * sh));

    // 5. Poświata za znakiem (rośnie z rysowaniem, potem lekko opada) i ziarno przeciw pasom w gradientach.
    float bloomEnv = u_motion > 0.5 ? mix(ease(t / 1.2), 0.72, ease((t - 1.2) / 1.6)) : 0.72;
    vec2 qc = q - vec2(0.0, -0.02);
    float bloom = exp(-dot(qc, qc) / 0.075) * 0.26 * bloomEnv;
    col = mix(col, vec3(1.0, 0.985, 0.965), bloom);

    col += (hash(gl_FragCoord.xy + fract(t) * 17.0) - 0.5) * (2.0 / 255.0);
    gl_FragColor = vec4(col, 1.0);
}
`;

// Linie znaku: każdy odcinek to czworokąt rozszerzany w przestrzeni ekranu (stała grubość w pikselach, miękkie krawędzie).
const LINE_VERTEX = `
attribute vec3 a_p0;      // początek odcinka (jednostki znaku, z dodatnie bliżej widza)
attribute vec3 a_p1;      // koniec odcinka
attribute vec2 a_corner;  // (strona -1/1, koniec 0/1)
attribute vec3 a_time;    // (opóźnienie s, czas rysowania s, długość odcinka w jednostkach znaku)

uniform mat4 u_mvp;       // model (obrót kamery wokół znaku) × widok × rzut, dla całej sceny
uniform float u_dist;     // odległość kamery (jednostki znaku): plany w głębi są skalowane, by na wprost dać rzut 2D
uniform vec2 u_scene;     // rozmiar sceny w pikselach
uniform vec4 u_remap;     // (sx, tx, sy, ty): z NDC całej sceny do NDC tego płótna
uniform vec2 u_center;    // przesunięcie środka znaku w NDC sceny
uniform float u_half;     // połowa szerokości czworokąta w pikselach (z zapasem na poświatę)

varying float v_side;     // odległość od osi linii w pikselach (ze znakiem)
varying float v_t;        // położenie wzdłuż odcinka w jednostkach znaku (z zapasem na zakończenia)
varying vec3 v_time;
varying vec2 v_q;         // położenie na ekranie jak w shaderze ściany
varying float v_z;

vec2 project(vec3 p) {
    p.xy *= (u_dist - p.z) / u_dist;
    vec4 c = u_mvp * vec4(p, 1.0);
    return c.xy / c.w + u_center;
}

void main() {
    vec2 n0 = project(a_p0);
    vec2 n1 = project(a_p1);
    vec2 s0 = (n0 + 1.0) * 0.5 * u_scene;
    vec2 s1 = (n1 + 1.0) * 0.5 * u_scene;
    vec2 d = s1 - s0;
    float len = max(length(d), 0.001);
    vec2 dir = d / len;
    vec2 nor = vec2(-dir.y, dir.x);
    float cap = u_half;
    vec2 s = mix(s0, s1, a_corner.y) + nor * a_corner.x * u_half + dir * (a_corner.y * 2.0 - 1.0) * cap;
    vec2 ndc = s / u_scene * 2.0 - 1.0;
    float aspect = u_scene.x / u_scene.y;

    v_side = a_corner.x * u_half;
    v_t = (mix(0.0, len, a_corner.y) + (a_corner.y * 2.0 - 1.0) * cap) * (a_time.z / len);
    v_time = a_time;
    v_q = vec2(ndc.x * aspect, -ndc.y) * 0.5;
    v_z = mix(a_p0.z, a_p1.z, a_corner.y);
    gl_Position = vec4(ndc.x * u_remap.x + u_remap.y, ndc.y * u_remap.z + u_remap.w, 0.0, 1.0);
}
`;

const LINE_FRAGMENT = `
precision mediump float;

uniform float u_time;
uniform float u_motion;
uniform float u_core;     // połowa grubości kreski w pikselach
uniform float u_fade;     // 1 widoczne, 0 wygaszone (po otwarciu drzwi)
uniform float u_pxu;      // pikseli na jednostkę znaku
uniform float u_k;

varying float v_side;
varying float v_t;
varying vec3 v_time;
varying vec2 v_q;
varying float v_z;

const vec3 INK = vec3(0.361, 0.122, 0.180);      // --brand #5c1f2e
const vec3 GLOW = vec3(1.0, 0.955, 0.895);

${SUN_GLSL}

void main() {
    float t = u_motion > 0.5 ? u_time : 10.0;
    float len = v_time.z;
    float progress = u_motion > 0.5 ? ease((t - v_time.x) / v_time.y) : 1.0;
    float drawn = progress * len;
    float d = abs(v_side);
    float tPx = v_t * u_pxu;
    float lenPx = len * u_pxu;
    float drawnPx = drawn * u_pxu;
    float started = step(0.0005, progress);              // odcinek nie istnieje, dopóki nie zaczął się rysować

    // Słońce na krawędzi: tafla pochylona w głąb mija najpierw plany bliższe widza. Na krawędziach jest węższa niż na ścianie.
    vec2 qm = vec2(v_q.x * u_look.x, v_q.y);
    float s = sunS(qm, t, u_k, v_z);
    float lit = smoothstep(0.30 * u_k, 0.06 * u_k, abs(s)) * sunBreath(t);

    // Kreska z miękką krawędzią, zakończenia prostopadłe, rysowana wzdłuż odcinka.
    float core = 1.0 - smoothstep(u_core - 0.6, u_core + 0.6, d);
    float head = 1.0 - smoothstep(drawnPx, drawnPx + 1.2, tPx);
    float ends = smoothstep(-u_core - 0.6, -u_core + 0.6, tPx) * (1.0 - smoothstep(lenPx + u_core - 0.6, lenPx + u_core + 0.6, tPx));
    float alpha = core * head * ends * started;

    // Jasny punkt na końcu rysowanej linii (tylko podczas rysowania).
    float drawing = step(0.001, progress) * step(progress, 0.999);
    float tip = exp(-pow((drawnPx - tPx) / 3.0, 2.0)) * drawing * core;

    // Światło słońca tylko odrobinę rozjaśnia linię, gdy ją mija. Bez poświaty wokół kreski: ciemna, rozmyta obwódka robiła ze znaku
    // plamę, a mocne rozjaśnienie wypłukiwało kolor. Znak ma być zawsze ostry i wyraźny (decyzja użytkownika z 2026-10-23).
    vec3 col = mix(INK, GLOW, clamp(lit * 0.1 + tip * 0.9, 0.0, 1.0));
    gl_FragColor = vec4(col, alpha * u_fade);
}
`;

// Cień rośliny w układzie całej sceny: kępki liści w prawych rogach i u góry (szerokość w vmax, obrót wokół środka jak w CSS).
// Zwraca płótno 2D z liśćmi w kanale alfa. Ścieżki SVG (leaflets, stems) są w układzie viewBox -150 -150 300 165.
function bakePlant(plant, sceneW, sceneH, width = 1024) {
    const canvas = document.createElement('canvas');
    const scale = width / sceneW;
    canvas.width = width;
    canvas.height = Math.max(1, Math.round(sceneH * scale));
    const ctx = canvas.getContext('2d');
    const vmax = Math.max(sceneW, sceneH) / 100;
    const leaflets = new Path2D(plant.leaflets);
    const stems = new Path2D(plant.stems);

    const draw = ({ w, right, top, bottom, angle, opacity }) => {
        const h = (w * 165) / 300;
        const left = sceneW - right - w;
        const y = top !== undefined ? top : sceneH - bottom - h;
        ctx.save();
        ctx.scale(scale, scale);
        ctx.translate(left + w / 2, y + h / 2);
        ctx.rotate((angle * Math.PI) / 180);
        ctx.translate(-w / 2, -h / 2);
        ctx.scale(w / 300, w / 300);
        ctx.translate(150, 150);
        ctx.globalAlpha = opacity;
        ctx.fillStyle = '#000';
        ctx.strokeStyle = '#000';
        ctx.lineCap = 'round';
        ctx.fill(leaflets);
        ctx.lineWidth = 2;
        ctx.stroke(stems);
        ctx.restore();
    };

    draw({ w: 62 * vmax, right: -16 * vmax, top: -18 * vmax, angle: 150, opacity: 1 });
    draw({ w: 52 * vmax, right: 0, bottom: -24 * vmax, angle: -20, opacity: 0.85 });
    draw({ w: 50 * vmax, right: 34 * vmax, top: -27 * vmax, angle: 118, opacity: 0.7 });

    return canvas;
}

/** Odcinki ścieżki SVG z poleceń M, L, H, V i Z (współrzędne bezwzględne): [[x0, y0, x1, y1], ...]. */
export function pathSegments(d) {
    let x = 0;
    let y = 0;
    let startX = 0;
    let startY = 0;
    const segments = [];

    for (const [, command, args] of d.matchAll(/([MLHVZ])([^MLHVZ]*)/g)) {
        const n = args.trim() ? args.trim().split(/[\s,]+/).map(Number) : [];
        let nx = x;
        let ny = y;

        if (command === 'M' || command === 'L') [nx, ny] = n;
        if (command === 'H') nx = n[0];
        if (command === 'V') ny = n[0];
        if (command === 'Z') [nx, ny] = [startX, startY];
        if (command !== 'M') segments.push([x, y, nx, ny]);
        if (command === 'M') [startX, startY] = [nx, ny];

        [x, y] = [nx, ny];
    }

    return segments;
}

// Głębia planów znaku (jednostki znaku, dodatnie bliżej widza): stół (drzwi) najbliżej, ramka w środku, obrys szlifu najgłębiej.
// Plan punktu poznajemy po współrzędnej x z signet.js (drzwi 38,5/81,5, ramka 34,5/85,5, reszta to obrys).
const DEPTH = 16;
function depthOf(x) {
    if (x === 38.5 || x === 81.5) return 0.35 * DEPTH;
    if (x === 34.5 || x === 85.5) return 0;
    return -DEPTH;
}

// Geometria linii: dla każdego odcinka 4 wierzchołki (p0, p1, narożnik, czasy). Czas rysowania ścieżki dzieli się między
// jej odcinki proporcjonalnie do długości, więc wielokąt rysuje się ciągłym ruchem jak w SVG.
function buildLines(strokes) {
    const data = [];
    const indices = [];
    let base = 0;

    for (const [d, dur, delay] of strokes) {
        const segments = pathSegments(d);
        const lengths = segments.map(([x0, y0, x1, y1]) => Math.hypot(x1 - x0, y1 - y0));
        const total = lengths.reduce((a, b) => a + b, 0);
        let before = 0;

        segments.forEach(([x0, y0, x1, y1], i) => {
            const p0 = [x0 - 60, 75 - y0, depthOf(x0)];
            const p1 = [x1 - 60, 75 - y1, depthOf(x1)];
            const time = [delay + (dur * before) / total, (dur * lengths[i]) / total, lengths[i]];
            for (const [side, end] of [[-1, 0], [1, 0], [-1, 1], [1, 1]]) {
                data.push(...p0, ...p1, side, end, ...time);
            }
            indices.push(base, base + 1, base + 2, base + 1, base + 3, base + 2);
            base += 4;
            before += lengths[i];
        });
    }

    return { data: new Float32Array(data), indices: new Uint16Array(indices), count: indices.length };
}

// Macierze 4×4 (kolumnami, jak w WebGL).
const mat = {
    multiply(a, b) {
        const out = new Float32Array(16);
        for (let c = 0; c < 4; c++) {
            for (let r = 0; r < 4; r++) {
                out[c * 4 + r] = a[r] * b[c * 4] + a[4 + r] * b[c * 4 + 1] + a[8 + r] * b[c * 4 + 2] + a[12 + r] * b[c * 4 + 3];
            }
        }
        return out;
    },
    perspective(fov, aspect, near, far) {
        const f = 1 / Math.tan(fov / 2);
        return new Float32Array([f / aspect, 0, 0, 0, 0, f, 0, 0, 0, 0, (far + near) / (near - far), -1, 0, 0, (2 * far * near) / (near - far), 0]);
    },
    translate(x, y, z) {
        return new Float32Array([1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, x, y, z, 1]);
    },
    rotateX(a) {
        const c = Math.cos(a);
        const s = Math.sin(a);
        return new Float32Array([1, 0, 0, 0, 0, c, s, 0, 0, -s, c, 0, 0, 0, 0, 1]);
    },
    rotateY(a) {
        const c = Math.cos(a);
        const s = Math.sin(a);
        return new Float32Array([c, 0, -s, 0, 0, 1, 0, 0, s, 0, c, 0, 0, 0, 0, 1]);
    },
};

// Generator pseudolosowy z ziarna (mulberry32): to samo ziarno zawsze daje ten sam układ światła.
function mulberry32(a) {
    return () => {
        a = (a + 0x6d2b79f5) | 0;
        let t = Math.imul(a ^ (a >>> 15), 1 | a);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

/**
 * Układ światła z ziarna. Obliczenia w shaderach są zawsze w układzie, w którym słońce wpada z lewego górnego rogu i płynie w prawo;
 * `flip` (-1 przy nieparzystym ziarnie) odbija to w poziomie, więc słońce wpada z prawej i płynie w lewo. Reszta to kąt ruchu tafli
 * (lekko w górę lub w dół), skos jej osi, przesunięcie toru względem znaku (znak zawsze zostaje w świetle), szerokość, odstęp i strona
 * drugiej tafli oraz odwrócenie cienia rośliny w pionie. Zakresy są wąskie, żeby każdy układ był subtelny i przyjemny.
 */
export function lightFromSeed(seed) {
    const id = seed >>> 0;
    const rng = mulberry32(id);
    rng();
    rng();
    const range = (a, b) => a + (b - a) * rng();

    const sweep = range(-0.42, 0.42); // kąt ruchu tafli względem poziomu (rad, y w dół)
    const skew = range(-0.25, 0.85); // o ile oś tafli odchyla się od prostopadłej do ruchu (rad)
    const axis = [Math.sin(skew - sweep), Math.cos(skew - sweep)]; // oś tafli (zawsze w dół, więc światło słabnie ku dołowi)
    const perp = [axis[1], -axis[0]]; // prostopadła do osi, w stronę ruchu
    const off = range(-0.14, 0.08); // przesunięcie toru w poprzek względem środka znaku
    const along = range(-0.12, 0.12); // i wzdłuż osi

    return {
        flip: id % 2 ? -1 : 1,
        sun: [Math.cos(sweep), Math.sin(sweep), axis[0], axis[1]],
        shape: [
            perp[0] * off + axis[0] * along,
            -0.02 + perp[1] * off + axis[1] * along,
            range(0.88, 1.15),
            (rng() < 0.5 ? -1 : 1) * range(0.55, 0.8),
        ],
        plantFlip: rng() < 0.4 ? 1 : 0,
    };
}

const FOV = (26 * Math.PI) / 180;
const BREEZE_DEPTH = -34; // plan bryzy: między ścianą a znakiem
const ease = (x) => {
    x = Math.min(Math.max(x, 0), 1);
    return x * x * (3 - 2 * x);
};

function compile(gl, type, source) {
    const shader = gl.createShader(type);
    gl.shaderSource(shader, source);
    gl.compileShader(shader);
    if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
        throw new Error(gl.getShaderInfoLog(shader) || 'shader');
    }
    return shader;
}

function program(gl, vertex, fragment, uniformNames) {
    const p = gl.createProgram();
    gl.attachShader(p, compile(gl, gl.VERTEX_SHADER, vertex));
    gl.attachShader(p, compile(gl, gl.FRAGMENT_SHADER, fragment));
    gl.linkProgram(p);
    if (!gl.getProgramParameter(p, gl.LINK_STATUS)) {
        throw new Error(gl.getProgramInfoLog(p) || 'program');
    }
    const uniforms = {};
    for (const name of uniformNames) uniforms[name] = gl.getUniformLocation(p, name);
    return { program: p, uniforms };
}

function createLeaf(canvas, plantCanvas, lines, look) {
    const gl = canvas.getContext('webgl', {
        alpha: false,
        antialias: false,
        depth: false,
        stencil: false,
        premultipliedAlpha: false,
        preserveDrawingBuffer: false,
        powerPreference: 'low-power',
    });
    if (!gl) return null;

    const wall = program(gl, WALL_VERTEX, WALL_FRAGMENT, ['u_res', 'u_origin', 'u_scene', 'u_time', 'u_motion', 'u_breeze', 'u_plant', 'u_sun', 'u_shape', 'u_look']);
    const line = program(gl, LINE_VERTEX, LINE_FRAGMENT, [
        'u_mvp', 'u_dist', 'u_scene', 'u_remap', 'u_center', 'u_half', 'u_time', 'u_motion', 'u_core', 'u_fade', 'u_pxu', 'u_k',
        'u_sun', 'u_shape', 'u_look',
    ]);

    const quad = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, quad);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW);
    const quadAttr = gl.getAttribLocation(wall.program, 'a');

    const lineBuffer = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, lineBuffer);
    gl.bufferData(gl.ARRAY_BUFFER, lines.data, gl.STATIC_DRAW);
    const lineIndex = gl.createBuffer();
    gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, lineIndex);
    gl.bufferData(gl.ELEMENT_ARRAY_BUFFER, lines.indices, gl.STATIC_DRAW);
    const stride = 11 * 4;
    const lineAttrs = [
        [gl.getAttribLocation(line.program, 'a_p0'), 3, 0],
        [gl.getAttribLocation(line.program, 'a_p1'), 3, 12],
        [gl.getAttribLocation(line.program, 'a_corner'), 2, 24],
        [gl.getAttribLocation(line.program, 'a_time'), 3, 32],
    ];

    const texture = gl.createTexture();
    gl.bindTexture(gl.TEXTURE_2D, texture);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
    gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, plantCanvas);

    // Układ światła z ziarna jest stały przez całe intro, więc ustawiamy go raz w obu programach.
    for (const { program: handle, uniforms } of [line, wall]) {
        gl.useProgram(handle);
        gl.uniform4f(uniforms.u_sun, ...look.sun);
        gl.uniform4f(uniforms.u_shape, ...look.shape);
        gl.uniform2f(uniforms.u_look, look.flip, look.plantFlip);
    }

    gl.useProgram(wall.program);
    gl.uniform1i(wall.uniforms.u_plant, 0);
    gl.blendFunc(gl.SRC_ALPHA, gl.ONE_MINUS_SRC_ALPHA);

    return { gl, wall, line, quad, quadAttr, lineBuffer, lineIndex, lineAttrs, stride, texture, count: lines.count };
}

/**
 * Uruchamia scenę na podanych płótnach (po jednym w każdym skrzydle). `root` to nakładka intro (scena), `signet` to element
 * znaku w układzie strony (jego położenie i szerokość wyznaczają kamerę), `strokes` to ścieżki znaku z czasami z Intro.vue,
 * a `seed` to ziarno układu światła (lightFromSeed).
 * Zwraca { finish, destroy } albo null, gdy WebGL jest niedostępny (wtedy zostaje wersja CSS).
 */
export function createIntroLight({ root, canvases, signet, strokes, plant, motion = true, seed = 0 }) {
    if (typeof window === 'undefined' || !window.WebGLRenderingContext) return null;

    const look = lightFromSeed(seed);

    const leaves = [];
    let frame = 0;
    let alive = true;
    let fadeStart = null;
    const start = performance.now();
    const camera = { scene: [1, 1], aspect: 1, dist: 1000, center: [0, 0], pxu: 1 };

    const layout = () => {
        const rootRect = root.getBoundingClientRect();
        if (rootRect.width < 1 || rootRect.height < 1) return false;
        // Kreski znaku muszą być ostre, więc renderujemy w pełnej rozdzielczości urządzenia (do 1,5 x). Przy bardzo dużych
        // ekranach (powyżej ok. 2,4 mln pikseli) zmniejszamy ją, ale nie poniżej 0,75, żeby oszczędzić GPU: światło jest miękkie,
        // więc tego nie widać, a kreski mają wciąż ok. 1,3 piksela. Wcześniej było stałe 0,66 i znak był rozmyty (powiększany).
        const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
        const area = rootRect.width * rootRect.height * dpr * dpr;
        const scale = dpr * (area > 2.4e6 ? Math.max(0.75, Math.sqrt(2.4e6 / area)) : 1);
        const sceneW = Math.round(rootRect.width * scale);
        const sceneH = Math.round(rootRect.height * scale);
        const plantCanvas = bakePlant(plant, sceneW, sceneH);

        // Kamera: znak ma na ekranie dokładnie miejsce i szerokość elementu SVG (viewBox 120 jednostek).
        const mark = signet.getBoundingClientRect();
        const pxu = (mark.width * scale) / 120;
        const f = 1 / Math.tan(FOV / 2);
        camera.scene = [sceneW, sceneH];
        camera.aspect = sceneW / sceneH;
        camera.pxu = pxu;
        camera.dist = (f * sceneH) / (2 * pxu);
        const cx = ((mark.left + mark.width / 2 - rootRect.left) * scale) / sceneW;
        const cy = ((mark.top + mark.height / 2 - rootRect.top) * scale) / sceneH;
        camera.center = [cx * 2 - 1, 1 - cy * 2];

        for (const leaf of leaves) {
            // Skrzydło ma szerokość 50% + 1px, więc przy nieparzystej szerokości okna zaczyna się w połowie piksela i na szwie
            // obu płócien widać cienką linię. Szerokość skrzydeł ustawiamy więc w pełnych pikselach (zachodzą na siebie o 2 px).
            leaf.canvas.parentElement.style.width = `${Math.ceil(rootRect.width / 2) + 1}px`;
            const rect = leaf.canvas.getBoundingClientRect();
            const w = Math.max(1, Math.round(rect.width * scale));
            const h = Math.max(1, Math.round(rect.height * scale));
            leaf.canvas.width = w;
            leaf.canvas.height = h;
            const ox = (rect.left - rootRect.left) * scale;
            const oy = (rect.top - rootRect.top) * scale;
            leaf.origin = [ox, oy];
            // Z NDC całej sceny do NDC tego płótna (dokładnie, bez zaokrągleń, żeby obie połówki znaku się schodziły).
            const oyBottom = sceneH - oy - h;
            leaf.remap = [sceneW / w, (sceneW - 2 * ox - w) / w, sceneH / h, (sceneH - 2 * oyBottom - h) / h];
            const { gl, texture } = leaf;
            gl.bindTexture(gl.TEXTURE_2D, texture);
            gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, plantCanvas);
        }
        return true;
    };

    const render = () => {
        const now = performance.now();
        const time = (now - start) / 1000;
        const fade = fadeStart === null ? 1 : Math.max(0, 1 - (now - fadeStart) / 450);
        const t = motion ? time : 10;

        // Kamera zaczyna lekko z boku (od strony, z której wpada słońce) i z góry i ustawia się na wprost w czasie rysowania
        // (potem ledwie zauważalnie oddycha).
        const settle = motion ? 1 - ease(time / 3.1) : 0;
        const yaw = (-6 * settle * look.flip + (motion ? 0.5 * Math.sin(time * 0.6) : 0)) * (Math.PI / 180);
        const pitch = (3.5 * settle + (motion ? 0.35 * Math.sin(time * 0.45 + 1) : 0)) * (Math.PI / 180);
        const model = mat.multiply(mat.rotateX(pitch), mat.rotateY(yaw));
        const view = mat.translate(0, 0, -camera.dist);
        const proj = mat.perspective(FOV, camera.aspect, 1, camera.dist * 4);
        const mvp = mat.multiply(proj, mat.multiply(view, model));
        const k = Math.min(Math.max(camera.aspect / 1.5, 0.5), 1);
        const core = Math.max(0.55, 0.7 * camera.pxu);
        // Paralaksa planu bryzy: punkt na głębokości BREEZE_DEPTH przesuwa się przy obrocie kamery (jednostki q = wysokość sceny).
        const breeze = [(-Math.sin(yaw) * BREEZE_DEPTH * camera.pxu) / camera.scene[1], (Math.sin(pitch) * BREEZE_DEPTH * camera.pxu) / camera.scene[1]];

        for (const leaf of leaves) {
            const { gl, wall, line, canvas } = leaf;
            gl.viewport(0, 0, canvas.width, canvas.height);

            gl.disable(gl.BLEND);
            gl.useProgram(wall.program);
            gl.bindBuffer(gl.ARRAY_BUFFER, leaf.quad);
            gl.enableVertexAttribArray(leaf.quadAttr);
            gl.vertexAttribPointer(leaf.quadAttr, 2, gl.FLOAT, false, 0, 0);
            gl.uniform2f(wall.uniforms.u_res, canvas.width, canvas.height);
            gl.uniform2f(wall.uniforms.u_origin, leaf.origin[0], leaf.origin[1]);
            gl.uniform2f(wall.uniforms.u_scene, camera.scene[0], camera.scene[1]);
            gl.uniform1f(wall.uniforms.u_time, time);
            gl.uniform1f(wall.uniforms.u_motion, motion ? 1 : 0);
            gl.uniform2f(wall.uniforms.u_breeze, breeze[0], breeze[1]);
            gl.drawArrays(gl.TRIANGLES, 0, 3);
            gl.disableVertexAttribArray(leaf.quadAttr);

            gl.enable(gl.BLEND);
            gl.useProgram(line.program);
            gl.bindBuffer(gl.ARRAY_BUFFER, leaf.lineBuffer);
            gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, leaf.lineIndex);
            for (const [loc, size, offset] of leaf.lineAttrs) {
                gl.enableVertexAttribArray(loc);
                gl.vertexAttribPointer(loc, size, gl.FLOAT, false, leaf.stride, offset);
            }
            gl.uniform1f(line.uniforms.u_dist, camera.dist);
            gl.uniform2f(line.uniforms.u_scene, camera.scene[0], camera.scene[1]);
            gl.uniform4f(line.uniforms.u_remap, ...leaf.remap);
            gl.uniform2f(line.uniforms.u_center, camera.center[0], camera.center[1]);
            gl.uniform1f(line.uniforms.u_time, t);
            gl.uniform1f(line.uniforms.u_motion, motion ? 1 : 0);
            gl.uniform1f(line.uniforms.u_core, core);
            gl.uniform1f(line.uniforms.u_fade, fade);
            gl.uniform1f(line.uniforms.u_pxu, camera.pxu);
            gl.uniform1f(line.uniforms.u_k, k);

            // Kreski znaku (bez cienia rzucanego na ścianę: znak ma być ostry).
            gl.uniformMatrix4fv(line.uniforms.u_mvp, false, mvp);
            gl.uniform1f(line.uniforms.u_half, core + 4);
            gl.drawElements(gl.TRIANGLES, leaf.count, gl.UNSIGNED_SHORT, 0);
            for (const [loc] of leaf.lineAttrs) gl.disableVertexAttribArray(loc);
        }
    };

    const loop = () => {
        if (!alive) return;
        render();
        frame = requestAnimationFrame(loop);
    };

    const onResize = () => {
        if (!alive) return;
        if (layout() && !motion) render();
    };

    try {
        const lines = buildLines(strokes);
        const blank = bakePlant(plant, 16, 9, 16);
        for (const canvas of canvases) {
            const leaf = createLeaf(canvas, blank, lines, look);
            if (!leaf) return null;
            leaves.push({ ...leaf, canvas, origin: [0, 0], remap: [1, 0, 1, 0] });
        }
        if (!layout()) return null;
    } catch (e) {
        return null;
    }

    window.addEventListener('resize', onResize);
    if (motion) {
        loop();
    } else {
        render();
    }

    return {
        /** Wygasza znak (0,45 s), gdy drzwi zaczynają się otwierać. */
        finish() {
            if (fadeStart === null) fadeStart = performance.now();
            if (!motion && !frame) loop();
        },
        destroy() {
            alive = false;
            cancelAnimationFrame(frame);
            window.removeEventListener('resize', onResize);
            for (const { gl } of leaves) {
                const ext = gl.getExtension('WEBGL_lose_context');
                if (ext) ext.loseContext();
            }
        },
    };
}

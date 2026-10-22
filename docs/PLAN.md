# Plan realizacji: strona Drzwi do Piękna

Gabinet medycyny estetycznej w Łodzi (lek. Anita Symonowicz). Pełna przebudowa strony drzwidopiekna.pl (dziś WordPress, 43 adresy w sitemapie). Strona dla kobiet 35+, estetyka premium, **mobile-first**, silne SEO lokalne.

Źródła prawdy dla wyglądu:
- Widoki (płótno Claude Design): https://claude.ai/artifact/F2SDMXx97AAzUiSdQq4shS
- System UI (tokeny, komponenty CSS, księga marki): https://claude.ai/artifact/XtCDdAWWZND5jc9SmTdpWA

## Stan realizacji

**Faza 1 (MVP) jest zrobiona**: strona główna według wizualizacji klienta (hero ze zdjęciem i znakiem marki, pas zalet z ikonami, O mnie, kafle zabiegów, „Twoje piękno", przed/po z karuzelą, pas filozofii, opinie, kontakt), nagłówek, menu mobilne, pasek akcji na telefonie, stopka, animacja powitalna, SEO bazowe, testy (9) i build z SSR. Sprawdzone w przeglądarce na desktopie i w szerokości 390 px. Nie zmierzone: Lighthouse i prawdziwe urządzenia.

Decyzje podjęte przy implementacji:
- Wszystkie przyciski „Umów wizytę/konsultację" prowadzą na razie do rezerwacji w Moment.pl (`CLINIC_BOOKING_URL` w `config/clinic.php`). Własny formularz w fazie 2.
- Zdjęcia demo pochodzą z Pexels (`resources/images/CREDITS.md`). Potok `npm run images` kadruje je do proporcji komponentów, tworzy WebP w kilku szerokościach i manifest `resources/js/images.json` (kolor dominujący jako tło na czas ładowania). Zdjęcie hero ma preload (LCP). Zdjęcia nie nadają się na produkcję (rozpoznawalne osoby), a pary przed/po to filtr z jednego zdjęcia, tylko do pokazania układu.
- `robots.txt` i `sitemap.xml` są dynamiczne. Poza produkcją robots zwraca `Disallow: /`, żeby staging nie trafił do indeksu.
- Animacja powitalna: skrypt w `<head>` ustawia `html[data-intro="play"]` (raz na sesję, nie przy ograniczonym ruchu ani w trybie automatyzacji). `?intro` wymusza odtworzenie. Bez JS nakładka sama znika po ok. 4 s.
- Inertia 3 ma wtyczkę `@inertiajs/vite`, więc osobny `ssr.js` nie jest potrzebny. Strony leżą w `resources/js/pages` (małe litery, jak w konfiguracji pakietu).

**Faza 2 jest zrobiona**: podstrony O mnie (z zespołem i filozofią), Zabiegi (lista, 5 obszarów, 31 zabiegów), Efekty, Cennik (19 grup, 134 pozycje) i Kontakt z formularzem. Dane zabiegów, cennika i zespołu są w bazie (seedery z treściami ze starej strony klienta), więc w fazie 3 panel będzie je tylko edytował. Testy: 22, na SQLite i na MySQL.

Decyzje fazy 2:
- **Baza**: lokalnie i w testach MySQL (Laragon), w produkcji PostgreSQL. Migracje używają wyłącznie typów Blueprint, a DDL dla PostgreSQL wygenerowano z tych samych migracji (bigserial, klucze obce, indeksy). Workflow CI (`.github/workflows/tests.yml`) uruchamia testy na SQLite, MySQL 8.4 i PostgreSQL 17. Na PostgreSQL nie uruchomiono jeszcze testów, bo lokalnie go nie ma: pierwszy bieg CI to właściwy sprawdzian.
- **Przekierowania 301** ze starej strony: `/zabieg/{stary-slug}` na nowy adres zabiegu (kolumna `legacy_slug`), `/oferta`, `/o-firmie/...`, `/filozofia`. Zabieg PeptiGlow ma przekierowanie na obszar „Jakość skóry”. Artykuły bloga i resztę przekierowań dodano w fazie 4.
- **Formularz kontaktowy**: walidacja po polsku, zapis w `contact_requests` (bez adresu IP), e-mail do gabinetu (`MAIL_MAILER=log` lokalnie, SMTP w produkcji), honeypot, limit 5 zapytań na minutę, awaria poczty nie traci zgłoszenia. Tekst zgody RODO jest przykładowy.
- **Treści**: przypisanie zabiegów do obszarów, opisy i ceny pochodzą ze starej strony i wymagają potwierdzenia klientki. Pełne opisy mają tylko ClearLift, kwas hialuronowy i Geneo (reszta stron odpowiadała blokadą 402). Nazwy handlowe leków na receptę (toksyna botulinowa, hialuronidaza) świadomie pominięto, a ceny trzech preparatów toksyny zapisano jako zakresy.
- **Zespół**: Dorota Kapusta i Mikołaj Retkiewicz są w bazie jako niepublikowani, bo na starej stronie nie mają żadnego opisu ani roli.
- **Do fazy 3**: panel redakcyjny, tabela opinii, osobne zdjęcia lekarki i zespołu (dziś monogramy).

**Faza 3 jest zrobiona**: panel redakcyjny w Filament 5 pod `/admin` (po polsku, kolor marki). Redaktorka edytuje obszary i zabiegi (opis, wskazania, efekty, SEO, adres starej strony do przekierowania, publikacja, kolejność przeciąganiem), cennik (grupy z pozycjami w jednym widoku, ceny „od”, zakresy, pakiety) i zespół, a zgłoszenia z formularza przegląda i zamyka (statusy Nowe, Przeczytane, Załatwione, licznik nowych przy menu, otwarcie zgłoszenia oznacza je jako przeczytane). Pulpit pokazuje liczbę nowych zgłoszeń, zabiegów i pozycji cennika. Testy: 36 (14 dla panelu), na SQLite i MySQL. Sprawdzone w przeglądarce na desktopie i w szerokości 375 px.

Decyzje fazy 3:
- **Dostęp**: panel `/admin` z logowaniem, każdy zalogowany użytkownik to redaktor (`User::canAccessPanel`), bez publicznej rejestracji. Konta zakłada się poleceniem `php artisan make:filament-user`. Lokalnie `AdminUserSeeder` tworzy konto z `ADMIN_EMAIL` i `ADMIN_PASSWORD` z `.env`, a w produkcji nic nie robi (test to pilnuje). Role i uprawnienia dojdą, gdy będzie więcej niż jedna osoba.
- **Ochrona danych**: zgłoszenia mają dane pacjentek, więc panel jest za logowaniem, `robots.txt` w produkcji zawiera `Disallow: /admin`, a adres IP nie jest zapisywany. Zgłoszeń nie da się tworzyć w panelu, tylko przeglądać, zamykać i usuwać.
- **Spójność z witryną**: slug zabiegu i obszaru ma walidację (małe litery, cyfry, myślniki, unikalność), bo trafia do adresu i mapy strony. Obszaru z zabiegami nie da się usunąć z listy (klucz obcy kasuje kaskadowo, więc przycisk jest ukryty). Ukrycie zabiegu od razu zdejmuje go ze strony i z `sitemap.xml`.
- **Przenośność MySQL i PostgreSQL**: relacje mają wbudowane `orderBy`, a `DISTINCT` albo `MAX` z takim `ORDER BY` kończy się błędem w obu silnikach (MySQL 3065). W zapytaniach agregujących po relacji zawsze `->reorder()`. Przy okazji wyszło to w teście, a nie na produkcji.
- **Nie zrobione w tej fazie**: meta SEO dla stron statycznych (Home, O mnie, Cennik), retencja zgłoszeń (np. automatyczne usuwanie po roku, do ustalenia z RODO).

**Faza 3b jest zrobiona**: biblioteka zdjęć i opinie w panelu. Testy: 65 (SQLite i MySQL). Sprawdzone w przeglądarce, łącznie z prawdziwym wgraniem pliku przez formularz.
- **Biblioteka zdjęć** (panel: Treści, Zdjęcia): wgranie JPEG, PNG lub WebP (do 12 MB, od 480 do 6500 px). Serwis `App\Support\PhotoStore` (GD, bez nowych pakietów) zapisuje warianty WebP 480/960/1440/1920 px (bez powiększania), kolor dominujący jako tło na czas ładowania, wypala obrót z EXIF i usuwa metadane (w tym GPS). Zdjęcie ma obowiązkowy opis (alt). Tabela `photos`, pliki w `storage/app/public/photos`, adres względny `/storage/...`.
- **Wybór zdjęcia** w formularzach obszaru (kafel), zabiegu (strona i podgląd linku Open Graph, JSON-LD `Service`) i osoby (portret zamiast monogramu): lista z miniaturami albo wgranie nowego z okna „Wgraj nowe zdjęcie” bez opuszczania formularza. Zdjęcia użytego gdzieś nie da się usunąć (z miejsca na stronie zniknęłoby po cichu). Obszary bez wybranego zdjęcia pokazują dalej zdjęcie demonstracyjne z potoku `npm run images` (`Images::resolve`). `php artisan photos:import-demo` wgrywa je do biblioteki, żeby dało się je podmienić z panelu.
- **Opinie** (panel: Treści, Opinie; tabela `testimonials`): sekcja na stronie głównej czyta je z bazy i znika, gdy nic nie jest opublikowane. Opinia widoczna na stronie musi być opublikowana i mieć potwierdzoną zgodę pacjentki (sprawdza to model, nie tylko formularz). Opinia przykładowa (`is_demo`) pokazuje się z uwagą „Przykładowa opinia z wizualizacji”, a seeder nie zakłada jej w produkcji.
- **Nie zrobione**: kadrowanie ostrości zdjęcia (punkt skupienia dla twarzy), wgrywanie wielu zdjęć naraz, tekst alternatywny podpowiadany automatycznie, osobna strona z opiniami i zdjęcia przed/po w panelu (nadal z potoku `npm run images`, bo wymagają zgód pisemnych).
- Style `.ddp-person__photo` i `.ddp-article__photo` dodano w `resources/css/ddp/components.css`. **Zsynchronizowane z artefaktem systemu UI (2026-10-22, wersja 9)**, razem z intro (błyski, drzwi), strzałką przewijania, paralaksą hero, notice, spisem treści i galerią. Od fazy 4 dochodzą też `.ddp-posts`, `.ddp-postcard*`, `.ddp-post*`, `.ddp-prose*`, `.ddp-figure*` i `.ddp-gallery*`.

**Faza 4 jest zrobiona w części technicznej** (blog, przekierowania, przygotowanie wdrożenia). Samego wdrożenia na serwerze jeszcze nie było, a start blokują sprawy po stronie klienta i kilka braków (lista w `docs/DEPLOY.md`, „Co jeszcze blokuje start”). Instrukcja wdrożenia: [docs/DEPLOY.md](DEPLOY.md).

Blog:
- Tabela `posts`, panel (Treści, Blog), strony `/blog` i `/blog/{slug}`, link w menu, SEO (JSON-LD `BlogPosting`, okruszki, `og:type=article`, wpisy w `sitemap.xml`), planowanie publikacji (przyszła data), czas czytania, zdjęcie główne wpisu (lista i podgląd linku).
- **Edytor wizualny** (Filament `RichEditor`) z paskiem: pogrubienie, pochylenie, podkreślenie, linki, nagłówki H2/H3, wyrównanie, cytat, listy, linia pozioma, **tabela** oraz przycisk „Bloki”: **Zdjęcie** (z biblioteki, podpis, całą szerokość albo węższe) i **Zdjęcia obok siebie** (2 lub 3). W bazie zostaje HTML z ustawieniami bloków (tylko identyfikatory zdjęć), więc wersje na telefon, srcset i opis alternatywny zawsze pochodzą z biblioteki. Tabela `photo_post` zapisuje, które zdjęcia są w treści: użytego zdjęcia nie da się usunąć z biblioteki. Zdjęcie usunięte mimo to nie zostawia pustej ramki. Wgrywanie plików prosto do tekstu jest wyłączone (zdjęcia tylko przez bibliotekę). HTML przechodzi przez sanityzator Filament (znikają skrypty, `onerror`, `javascript:`, ramki).
- **Cztery artykuły** ze starej strony, ze starymi adresami, datami i autorami (dr Anita Symonowicz oraz Katarzyna Klepacz, kosmetolog spoza tabeli zespołu, więc wpis ma własne pola autora). Treść przeniesiona z poprawkami zgodnymi z zasadami projektu (pełna lista w nagłówku `database/seeders/PostSeeder.php`): usunięta nazwa substancji wydawanej na receptę, złagodzone obietnice i superlatywy („gwarantującego”, „idealna”), usunięte porównanie, że „tylko lekarze, nie kosmetolodzy znają anatomię”, poprawione literówki, linki do zabiegów na nowe adresy. **Bez zdjęć**: stare były stockowe, cudze (CBS News) albo przedstawiały prywatną osobę, a ich pobranie i licencje wymagają osobnej zgody. Zdjęcia dodaje się w panelu. Wymagają przeglądu klientki i prawnika, w tym porad zdrowotnych (witamina D3) w artykułach kosmetologa.

Przekierowania 301 (decyzje):
- Wszystkie 70 adresów z map starej strony (generator i Yoast, `tests/fixtures/old-site-paths.txt`) kończy na działającej stronie po **jednym** skoku. Sprawdza to test (`LegacyUrlsTest`) i skrypt na serwerze (`deploy/check-redirects.sh`).
- Ukośnik końcowy (`/cennik/` na `/cennik`) normalizuje middleware `RedirectTrailingSlash` (wcześniej oba warianty zwracały 200, czyli duplikaty). Adresy, które i tak przekierowują, zostają dla swojego przekierowania (jeden skok, nie dwa).
- **Luka z fazy 2, naprawiona:** pięć starych adresów zabiegów nie miało odpowiednika i dawałoby 404 (`lifting-twarzy-nicmi`, `lipoliza-iniekcyjna`, `masaze`, `nucleofill`, `retibooster`). Dostały przekierowania na najbliższą listę lub zabieg.
- Dodane: `/faq` na `/kontakt`, wersja angielska (`/en/...`, w tym zabiegi) na polskie odpowiedniki (nowa strona jest tylko po polsku), archiwum `/zabieg`, stare mapy WordPressa (`sitemap_index.xml` itd.) na `/sitemap.xml`. Mapa jest w `config/clinic.php` (`legacy_redirects`, `legacy_fallbacks`, `legacy_aliases`).
- Adresy artykułów są takie same jak na starej stronie.

Polityka prywatności i cookies (faza 4):
- Strona `/polityka-prywatnosci` ze spisem treści (nagłówki H2 dostają kotwice, sekcja o cookies ma stałą `#cookies`), edytowana w panelu (Treści, Dokumenty; edytor wizualny z tabelą; dokument ma stały adres, więc nie da się go założyć ani usunąć). Przy zgodzie w formularzu kontaktowym jest link do niej (nowa karta), w stopce linki „Polityka prywatności” i „Polityka cookies” (wcześniej były to martwe napisy).
- **Szkielet treści** (`PageSeeder`): administrator, dane i cele, podstawy prawne, odbiorcy, okresy przechowywania, prawa, skarga do UODO, dobrowolność, cookies. Fakty są z kodu i pilnuje ich test (`LegalPageTest`): lista cookies zgadza się z tym, co strona naprawdę ustawia (`drzwi-do-piekna-session`, `XSRF-TOKEN`, 2 godziny, plus wpis `ddp-intro` w sessionStorage), brak kolumny z IP w zgłoszeniu, brak skryptów analitycznych w szablonie. Dlatego baner cookies nie jest potrzebny, dopóki nie pojawi się analityka (test zmusi wtedy do aktualizacji dokumentu). Decyzje prawne to pola w nawiasach kwadratowych dla prawnika klienta (19 pól).
- **Wersja robocza:** dopóki dokument jest oznaczony jako czekający na zatwierdzenie albo zawiera pola w nawiasach, strona pokazuje dopisek, ma `noindex` i nie trafia do `sitemap.xml`. Panel nie pozwoli oznaczyć dokumentu jako zatwierdzonego z niewypełnionymi polami ani bez daty wejścia w życie. `php artisan legal:check` (sprawdza też zgodę w `lang/pl/pages.php`) jest uruchamiany przez `deploy.sh` i **blokuje wdrożenie produkcyjne**.
- **Błąd z fazy 3, naprawiony przy okazji:** cały `config/clinic.php` trafiał do danych każdej strony publicznej, więc w jej HTML lądowały dane konta deweloperskiego panelu (e-mail i hasło z `.env`) oraz mapy przekierowań. Teraz przekazujemy tylko białą listę pól (`HandleInertiaRequests::PUBLIC_CLINIC_KEYS`), z testem. Hasło konta deweloperskiego w lokalnym `.env` warto zmienić.

Dwuskładnikowe logowanie do panelu (2FA):
- Filament `AppAuthentication` (TOTP, np. Google Authenticator) z kodami awaryjnymi (8, każdy działa raz, zapisane jako skróty). Sekret i kody są szyfrowane kluczem aplikacji i ukryte w serializacji modelu `User`. Strona profilu (`/admin/profile`, w pełnym układzie panelu) pozwala skonfigurować 2FA, wygenerować nowe kody i wyłączyć je.
- **Wymagane w produkcji** (`config('clinic.admin.require_2fa')`: domyślnie `APP_ENV=production`, wymusza je też `ADMIN_2FA_REQUIRED=true`): konto bez 2FA po zalogowaniu trafia na ekran konfiguracji. Lokalnie niewymagane, żeby konto deweloperskie było używalne. `deploy.sh` nie pozwoli wdrożyć produkcji z wyłączonym wymogiem.
- Utrata telefonu i kodów: `php artisan admin:reset-2fa adres@konta.pl` (administrator serwera, z potwierdzeniem). Zmiana `APP_KEY` unieważnia sekrety (konta trzeba zresetować).
- Sprawdzone w prawdziwym panelu: konfiguracja (QR, klucz, kod, hasło, kody odzyskiwania), logowanie hasłem zatrzymuje się na ekranie kodu, kod z aplikacji loguje, reset. Testy: logowanie hasłem i kodem, zły kod, kod awaryjny (jednorazowy), szyfrowanie w bazie, wymóg w produkcji (przez ponowne uruchomienie aplikacji z innym środowiskiem), polecenie resetu.

Potwierdzenie e-mail dla osób z formularza:
- Jeśli ktoś poda adres e-mail, dostaje ładną wiadomość „Dziękujemy za zgłoszenie” (szablon HTML w marce: znak, bordowy pasek, serifowy nagłówek, kroki „Co dalej”, przycisk rezerwacji online, dane gabinetu, informacja „jeśli to nie Ty” i link do polityki prywatności, plus wersja tekstowa). Znak marki jest wysyłany jako osadzony PNG (`resources/mail/signet.png`, generowany `npm run mail-assets`), bez obrazków z internetu. Podgląd tylko lokalnie: `/_podglad/email/potwierdzenie`.
- **Prywatność:** wiadomość nie powtarza numeru telefonu ani treści z formularza (poczta nie jest szyfrowana, a formularz mógł dotyczyć zdrowia), tylko imię i obszar zabiegu. Imię jest escapowane.
- **Ochrona przed nadużyciem:** formularz jest publiczny, a potwierdzenie idzie na adres wpisany przez nieznaną osobę, więc do jednego adresu wysyłamy najwyżej dwa potwierdzenia na godzinę (limit po skrócie adresu, niezależnie od wielkości liter). Zgłoszenie zapisuje się zawsze, a awaria poczty nie psuje formularza ani powiadomienia dla gabinetu. W panelu zgłoszenie pokazuje, kiedy wysłano potwierdzenie (`confirmation_sent_at`). Polityka prywatności o tym informuje.

Strona główna (hero i animacja powitalna):
- Zamiast napisu „Scroll” z kreską (nachodził na przycisk przy niższym oknie) jest klikalna strzałka w cienkim kółku, która delikatnie opada i znika; prowadzi płynnie do pasa zalet, bez dopisywania `#zalety` do adresu, z przeniesieniem fokusu. Płynne przewijanie było już w `site.css` (`scroll-behavior: smooth`) i działa, chyba że system ma włączone ograniczenie ruchu.
- **Paralaksa zdjęcia hero** (`v-parallax`, `resources/js/directives/parallax.js`): zdjęcie przesuwa się wolniej niż strona (do 12% wysokości ramki), tylko przez `transform`, tylko gdy jest widoczne, raz na klatkę, wyłączona przy ograniczonym ruchu.
- **Otwieranie drzwi po animacji powitalnej:** po narysowaniu sygnetu i napisu (3 s) napis znika, a dwa zwykłe prostokąty (skrzydła, bez ramek paneli) od razu rozchylają się na boki (około 1,4 s) i odsłaniają stronę. Zachowuje zasadę projektu (bez obrotu i 3D): to skala w poziomie ściskająca każde skrzydło do jego zewnętrznej krawędzi, a nie obrót na zawiasach. Przy ograniczonym ruchu animacja nie gra. Kliknięcie lub klawisz pomija czekanie i otwiera drzwi od razu. Cały film trwa teraz około 5 s zamiast 3,7 s.

Przygotowanie wdrożenia:
- `deploy/`: konfiguracja nginx (domena bez www, HTTPS, cache zasobów, limit uploadu), Supervisor dla SSR, `deploy.sh` (sprawdzenie środowiska, przerwa 503, build, migracje, cache, restart, test), `backup.sh` (PostgreSQL i zdjęcia), `check-redirects.sh`, `check-ssr.sh`. Szablon `.env.production.example`.
- Ekrany błędów 404, 500 i 503 w marce, bez zależności od Vite i JS.
- CI: job `production-build` (`composer install --no-dev`, build klienta i SSR, `php artisan optimize`), poprawione rozszerzenia PHP (`gd`, `exif`, `intl`). Lokalnie sprawdzone, że cache konfiguracji, tras (26 tras przekierowań), widoków, zdarzeń i Filament się buduje.
- **Nie zrobione:** polityka prywatności i prawdziwa zgoda RODO (od klienta), dwuskładnikowe logowanie do panelu, repozytorium git, pomiar Lighthouse, uruchomienie na PostgreSQL, retencja zgłoszeń.

Uruchomienie lokalnie: `php artisan serve` i `npm run dev`. Produkcja: `npm run build`, `php artisan inertia:start-ssr` (proces Node nadzorowany przez Supervisor lub systemd).

## 1. Decyzje technologiczne

| Obszar | Wybór | Dlaczego |
| --- | --- | --- |
| Backend | Laravel 13, PHP 8.4 | Stack zespołu, dojrzały ekosystem, łatwy panel redakcyjny |
| Baza | PostgreSQL (produkcja), MySQL lokalnie | Stack zespołu. Kod przenośny: tylko typy Blueprint, bez surowego SQL; testy na SQLite, MySQL i PostgreSQL (CI). Sesje i cache na plikach |
| Frontend | Vue 3 (`<script setup>`) + Inertia.js 3 | Jedna aplikacja, routing i walidacja w Laravelu, brak osobnego API |
| SEO | Inertia **SSR** (wtyczka `@inertiajs/vite`, Node 22+) + meta i JSON-LD z szablonu Blade | Pełny HTML dla robotów i szybki pierwszy render na telefonie |
| Style | Czysty CSS z systemu UI (`resources/css/ddp/*`), bez Tailwinda | Jeden plik komponentów używany w projekcie i w płótnie Design; spójność 1:1 |
| Animacje | CSS + SVG (`stroke-dashoffset`), bez bibliotek | Animacja powitalna to rysowanie linii; zero kilobajtów JS na ruch |
| Fonty | `@fontsource` (Cormorant Garamond, Jost), własny hosting | RODO (brak Google Fonts) i wydajność |
| Testy | Pest (feature) + Lighthouse CI | Kontrola SEO i wydajności przy każdej zmianie |
| Panel (faza 3) | Filament 5 pod `/admin` | Redagowanie zabiegów, cennika, zespołu i zgłoszeń bez programisty (blog i opinie dojdą później) |

## 2. Architektura

```
app/Http/Controllers/        HomeController, SeoController (robots.txt, sitemap.xml)
app/Support/Seo.php          title, description, canonical, OG, JSON-LD
config/clinic.php            dane gabinetu: adres, telefon, social, nawigacja (jedno źródło)
lang/pl/home.php             treści strony głównej
resources/css/ddp/           tokens.css + components.css (z systemu UI, bez zmian)
resources/css/site.css       układ stron mobile-first
resources/js/app.js          start Inertia (klient i SSR, opcja withApp)
resources/js/layouts/        SiteLayout.vue (Intro, SiteHeader, SiteFooter, ActionBar)
resources/js/components/     Logo, Signet, SiteHeader, SiteFooter, ActionBar, Intro,
                             PhotoFrame, TreatmentCard, Pillar, Band, ArrowIcon
resources/js/directives/     reveal.js (v-reveal)
resources/js/pages/          Home.vue (kolejne strony w fazie 2)
resources/views/app.blade.php  szkielet HTML: meta, JSON-LD i skrypt animacji w <head>
```

Zasady: treść w `lang/pl` i `config/clinic.php`, nie w komponentach; komponent Vue dokłada zachowanie do klasy `ddp-*`, nie dubluje stylów; jeden `h1` na stronę; każde zdjęcie przez `PhotoFrame` (wymiary, `srcset`, `alt`).

## 3. Animacja powitalna

- Nakładka `ddp-intro` rysuje sygnet (drzwi, szlif, obrys), potem pojawia się nazwa i napis MEDYCYNA ESTETYCZNA. Około 2,8 s, sceny i czasy jak w storyboardzie na płótnie.
- Gra raz na sesję (`sessionStorage`), da się pominąć dotykiem lub klawiszem. Przy `prefers-reduced-motion` nie gra.
- Nie opóźnia pierwszego renderu: strona ładuje się pod nakładką. Gdy JS zawiedzie, nakładka nie może zasłaniać treści (domyślnie ukryta, pokazuje ją skrypt).
- Zakazane: połysk, glow, cząsteczki, obrót, 3D, morfing.

## 4. SEO

Techniczne
- SSR każdej strony. `lang="pl"`, jeden `h1`, logiczne `h2`/`h3`.
- Per strona: `title` (do 60 znaków), `meta description` (do 155), `canonical`, Open Graph i Twitter Card, obraz społecznościowy 1200×630.
- Dane strukturalne (JSON-LD): `MedicalBusiness` z adresem, telefonem, godzinami, `sameAs` (Facebook, Instagram, ZnanyLekarz), `Physician` dla dr Anity Symonowicz, `BreadcrumbList`, `Service` dla obszarów zabiegów, `Article` dla bloga, `FAQPage` tam, gdzie jest sekcja pytań.
- `sitemap.xml` (generowany), `robots.txt`, jedna kanoniczna domena (https, bez/z www do ustalenia), spójna polityka ukośnika końcowego.
- Zdjęcia: AVIF/WebP, `srcset`, `width`/`height`, obraz LCP z `fetchpriority="high"`, reszta `loading="lazy"`. Fonty z preloadem i `font-display: swap`.
- Cele Core Web Vitals na telefonie (4G): LCP do 2,5 s, CLS poniżej 0,1, INP poniżej 200 ms. Budżet JS strony głównej: poniżej 120 kB gzip.

Lokalne i treściowe
- Wizytówka Google (g.page/drzwidopiekna) i dane NAP (nazwa, adres, telefon) identyczne wszędzie: strona, Google, ZnanyLekarz, Facebook.
- Frazy lokalne w tytułach i nagłówkach podstron: „medycyna estetyczna Łódź", „gabinet medycyny estetycznej Łódź" oraz obszary (usta, szyja i dekolt, profil). Unikalne teksty na każdej podstronie, bez kopiowania ze starej strony.
- **Migracja z WordPressa:** zachować wartość starych adresów. Mapa 301 ze starych URL (`/zabieg/...`, `/o-mnie/`, `/o-firmie/nasz-zespol/...`, `/cennik/`, `/blog/...`, `/kontakt/`) na nowe. Lista 43 adresów jest w sitemapie starej strony. Bez mapy 301 strona straci obecne pozycje.
- Narzędzia: Google Search Console od pierwszego dnia, analityka (GA4 lub Plausible) z obsługą zgody.

## 5. Telefon i dostępność

- Projekt od 390 px, punkty graniczne 640 i 1024 px. Elementy dotykowe co najmniej 48 px, pola formularzy 16 px, `svh` zamiast `vh`, `env(safe-area-inset-*)`.
- Przyklejony pasek Zadzwoń / Umów wizytę na telefonie, menu pełnoekranowe z pułapką fokusu.
- WCAG 2.2 AA: kontrasty sprawdzone w tokenach, widoczny fokus, link „Przejdź do treści", obsługa klawiatury, `prefers-reduced-motion`.

## 6. Prawo i prywatność

- RODO: polityka prywatności, zgoda przy formularzu (treść zatwierdza klient), cookies tylko gdy jest analityka, brak osadzonych map i fontów z zewnętrznych serwerów bez zgody.
- Reklama działalności leczniczej: bez obietnic wyniku, bez superlatywów, bez nazw handlowych leków na receptę (m.in. toksyny botulinowej) w treściach publicznych, zdjęcia przed/po tylko za pisemną zgodą. **Do potwierdzenia z prawnikiem klienta.**

## 7. Fazy

**Faza 1: MVP (ten etap)**
- Szkielet Laravel + Vue 3 + Inertia + SSR, system UI przeniesiony do `resources/css/ddp`.
- Layout: Header, menu mobilne, ActionBar, Footer. Animacja powitalna.
- Strona główna ze wszystkimi sekcjami z projektu (treści statyczne, zdjęcia jako pola zastępcze).
- SEO bazowe: title, description, canonical, OG, JSON-LD `MedicalBusiness`, `robots.txt`, `sitemap.xml`.
- Test funkcjonalny strony głównej. Kryterium: Lighthouse mobile co najmniej 90 (wydajność) i 100 (SEO, dostępność, dobre praktyki).

**Faza 2: podstrony i baza (zrobiona, patrz „Stan realizacji”)**
- O mnie, Zespół (dr Katya Aleksandrowicz, Dorota Kapusta, Mikołaj Retkiewicz: do potwierdzenia z klientem), Zabiegi (lista + podstrona obszaru i zabiegu), Efekty, Cennik, Kontakt.
- PostgreSQL: `treatment_categories`, `treatments`, `team_members`, `price_items`, `testimonials`, `contact_requests`.
- Formularz kontaktowy: walidacja, zapis w bazie, e-mail do gabinetu, honeypot i limit zapytań, link do rezerwacji w Moment.pl.

**Faza 3: panel redakcyjny (zrobiona, patrz „Stan realizacji”)**
- Filament: obszary, zabiegi, cennik, zespół i zgłoszenia z formularza.
- Opinie i biblioteka zdjęć z konwersją do WebP: zrobione (faza 3b). Odłożone: meta SEO stron statycznych, AVIF.

**Faza 4: blog, migracja i start (część techniczna zrobiona, patrz „Stan realizacji” i docs/DEPLOY.md)**
- Blog (4 artykuły z obecnej strony), mapa 301, `sitemap` z treściami z bazy, kopie zapasowe: zrobione. Monitoring błędów: zewnętrzny monitoring `/up` i logi, bez narzędzia typu Sentry.
- Wdrożenie: VPS z nginx, php-fpm, PostgreSQL i procesem SSR Node pod Supervisorem: przygotowane (pliki w `deploy/`, instrukcja w `docs/DEPLOY.md`), jeszcze nie uruchomione na serwerze. Lista kontrolna startu: Search Console, Wizytówka Google, test 301 (`deploy/check-redirects.sh`), test formularza, Lighthouse.

## 8. Do dostarczenia przez klienta

- Oryginalne logo (SVG, najlepiej z warstwami: drzwi, szlif, obrys). Dziś w projekcie jest rekonstrukcja zastępcza.
- Zdjęcia: portret lekarki, wnętrze gabinetu, kadry kobiety 40–50 lat (hero i obszary zabiegów). Opcjonalnie sesja zdjęciowa.
- Prawdziwe opinie (z ZnanyLekarz lub Google) wraz ze zgodą, zdjęcia przed/po wraz ze zgodami.
- Godziny otwarcia, NIP i dane podmiotu, treść zgody RODO, polityka prywatności.
- Decyzja: rezerwacja pozostaje w Moment.pl czy własny formularz.

## 9. Ryzyka

- Zmiana adresów bez przekierowań (utrata pozycji). Łagodzenie: mapa 301 w fazie 4, przygotowywana od fazy 2.
- Brak zdjęć: pola zastępcze wyglądają dobrze, ale ostateczny efekt strony zależy od fotografii.
- Przepisy o reklamie medycznej: treści do weryfikacji prawnej przed publikacją.
- SSR wymaga procesu Node na serwerze: potwierdzić hosting przed fazą 4.

# Wdrożenie: drzwidopiekna.pl

Instrukcja dla jednego serwera VPS (Ubuntu 24.04 LTS, 2 GB RAM wystarczy): nginx, PHP-FPM 8.4, PostgreSQL 17,
Node 22 (renderowanie SSR) i Supervisor. Pliki konfiguracyjne są w katalogu `deploy/`. Nic z tego nie zostało jeszcze
uruchomione na prawdziwym serwerze: skrypty sprawdzono składniowo i na lokalnym serwerze deweloperskim,
konfiguracja nginx nie była testowana (`nginx -t` na serwerze to pierwszy krok po jej skopiowaniu).

## Co jeszcze blokuje start

Wdrożenie techniczne jest gotowe, ale **strony nie wolno jeszcze publikować jako nowej wersji drzwidopiekna.pl**, dopóki:

- [ ] **Polityka prywatności i zgoda RODO czekają na treść od prawnika.** Strona `/polityka-prywatnosci` i szkielet dokumentu są gotowe
      (panel: Treści, Dokumenty; zgoda w formularzu linkuje do niej, stopka też), a fakty o działaniu strony (jakie dane zbiera
      formularz, jakie cookies ustawia, że nie ma analityki) są wypełnione z kodu. Zostają pola w nawiasach kwadratowych
      (administrator, NIP, podstawy prawne, odbiorcy, okresy przechowywania) i pole „[Treść zgody do zatwierdzenia…]” w
      `lang/pl/pages.php`. Dopóki są niewypełnione, **`deploy.sh` blokuje wdrożenie produkcyjne** (`php artisan legal:check`;
      świadome obejście: `ALLOW_DRAFT_LEGAL=1 bash deploy/deploy.sh`), strona ma dopisek „Wersja robocza” i `noindex`.
      Po zatwierdzeniu treści trzeba jeszcze wdrożyć okres przechowywania zgłoszeń (automatyczne usuwanie).
- [ ] **Treści wymagają przeglądu prawnika klienta** (reklama działalności leczniczej): zabiegi, cennik, artykuły z bloga
      (lista zmian w `database/seeders/PostSeeder.php` i w `docs/PLAN.md`).
- [ ] **Zdjęcia są demonstracyjne** (Pexels, rozpoznawalne osoby, przed/po to jeden kadr z filtrem). Trzeba je zastąpić
      własnymi, a pary przed/po dodać tylko ze zgodami pacjentek.
- [ ] **Opinia „Anna" jest przykładowa** (oznaczona jako demo). Na produkcji seeder jej nie zakłada.
- [x] ~~Kod nie jest w repozytorium git~~ **Jest:** https://github.com/KenjiWriter/Drzwi-do-Piekna (gałąź `main`). Na serwer trafia przez `git clone` i `git pull`.
      Workflow CI (`.github/workflows/tests.yml`) uruchamia się na GitHubie: sprawdź, czy przechodzi (zwłaszcza job `postgres`).
- [ ] **PostgreSQL nigdy nie był uruchomiony z tą aplikacją na prawdziwym serwerze.** DDL sprawdzono generowaniem, a pierwszy prawdziwy test
      to CI (job `postgres`) i pierwsze wdrożenie na stagingu (poniżej).

## Staging: demo.cerasusdigital.pl

Pokazowa kopia strony na osobnej domenie, na Ubuntu 22.04 lub 24.04 (czystym albo współdzielonym z inną aplikacją: skrypt nie przestawia
domyślnego `php` ani istniejącego Node, nasza aplikacja używa `php8.4` obok nich, a Node wystarczy 20.19+ albo 22.12+). To `APP_ENV=staging`:
`robots.txt` blokuje indeksowanie, dokumenty prawne nie blokują wdrożenia, a poczta zapisuje wiadomości do logu, dopóki w `.env` nie ma SMTP.
Wszystko robi `deploy/provision-staging.sh` (pakiety, PHP 8.4, PostgreSQL, Node 22, nginx, certyfikat Let's Encrypt, pierwsze wdrożenie,
dane startowe, SSR pod Supervisorem). Przed uruchomieniem rekord A `demo.cerasusdigital.pl` musi wskazywać na ten serwer.

```bash
sudo apt update && sudo apt install -y git
sudo git clone https://github.com/KenjiWriter/Drzwi-do-Piekna.git /var/www/drzwidopiekna
sudo LE_EMAIL=twoj@adres.pl bash /var/www/drzwidopiekna/deploy/provision-staging.sh
cd /var/www/drzwidopiekna && sudo php artisan make:filament-user      # konto do /admin, hasło wpisujesz sam
```

Skrypt można uruchomić ponownie (nie nadpisuje `.env`, hasła bazy, konfiguracji nginx ani danych). Kolejne wdrożenia:

```bash
cd /var/www/drzwidopiekna && sudo git pull --ff-only && sudo EXPECT_ENV=staging SUDO="" bash deploy/deploy.sh
```

**Poczta (SMTP) na stagingu.** Do czasu uzupełnienia `.env` wiadomości zapisują się w logu (`MAIL_MAILER=log`). Ustaw w `/var/www/drzwidopiekna/.env`
(`sudo nano`) dane skrzynki (`MAIL_MAILER=smtp`, `MAIL_SCHEME=smtps`, `MAIL_HOST`, `MAIL_PORT=465`, `MAIL_USERNAME`, `MAIL_PASSWORD`,
`MAIL_FROM_ADDRESS` taki sam jak login skrzynki) oraz `CLINIC_NOTIFY_EMAIL` na własny adres, żeby powiadomienia z testów nie szły do gabinetu.
Potem **`sudo php8.4 artisan config:cache && sudo systemctl reload php8.4-fpm`**. Nie używaj samego `config:clear`: `.env` jest własnością roota
(`chmod 600`), PHP-FPM go nie czyta i działa z zapisanej konfiguracji, więc po `config:clear` aplikacja przestałaby działać do kolejnego `config:cache`.
Skrzynka, z której wysyłamy z domeny `drzwidopiekna.pl`, wymaga rekordów SPF i DKIM u dostawcy poczty (inaczej wiadomości trafią do spamu).

Na stagingu panel domyślnie wymaga 2FA (`ADMIN_2FA=false` przy uruchomieniu skryptu albo `ADMIN_2FA_REQUIRED=false` w `.env` to wyłącza).
Gdy gabinet ma oglądać demo bez widocznego adresu dla wszystkich, można dodatkowo zabezpieczyć całą domenę hasłem nginx (`auth_basic`).
- [x] ~~Brak dwuskładnikowego logowania do `/admin`~~ **Jest** (aplikacja uwierzytelniająca + kody awaryjne, w produkcji wymagane, patrz punkt 11).
- [ ] **Poczta z potwierdzeniem dla klientek.** Formularz wysyła teraz automatyczne potwierdzenie na adres podany przez osobę (oprócz
      powiadomienia dla gabinetu), więc SMTP, SPF i DKIM domeny muszą działać, zanim ruszy produkcja (inaczej wiadomości trafią do spamu).
      Wyślij testowe zgłoszenie z własnym adresem e-mail i obejrzyj wiadomość w kilku klientach poczty.
- [ ] **Nie zmierzono Lighthouse** (cel: wydajność mobile od 90, SEO i dostępność 100). Zmierz na stagingu.

## 1. Serwer

```bash
sudo apt update && sudo apt install -y nginx postgresql supervisor certbot python3-certbot-nginx git unzip curl

# PHP 8.4 (repozytorium ondrej/php) i rozszerzenia wymagane przez aplikację
sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install -y php8.4-fpm php8.4-cli php8.4-pgsql php8.4-gd php8.4-intl php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip

# Node.js 22 LTS (potrzebny do budowy frontu i do procesu SSR)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
```

Rozszerzenie `gd` musi obsługiwać WebP, a `exif` służy do obrotu zdjęć z telefonu. `deploy.sh` sprawdza to przed wdrożeniem.

Ustawienia PHP w `/etc/php/8.4/fpm/conf.d/99-drzwidopiekna.ini`:

```ini
expose_php = Off
memory_limit = 256M
upload_max_filesize = 12M
post_max_size = 14M
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
; Bez sprawdzania znaczników czasu plików: po każdym wdrożeniu PHP-FPM jest przeładowywany (robi to deploy.sh).
opcache.validate_timestamps = 0
```

## 2. Baza danych

```bash
sudo -u postgres psql -c "CREATE ROLE drzwi LOGIN PASSWORD 'TU-DLUGIE-LOSOWE-HASLO';"
sudo -u postgres createdb -O drzwi -E UTF8 drzwi_do_piekna
```

Baza słucha tylko na `127.0.0.1` (domyślnie). Nie otwieraj portu 5432 na zewnątrz.

## 3. Aplikacja: pierwsze wdrożenie

```bash
sudo mkdir -p /var/www/drzwidopiekna /var/log/drzwidopiekna
sudo chown -R deploy:www-data /var/www/drzwidopiekna          # "deploy" to konto, z którego wdrażasz
sudo chown www-data /var/log/drzwidopiekna

cd /var/www/drzwidopiekna
git clone <adres-repozytorium> .          # albo: rsync -a --exclude node_modules --exclude vendor ./ serwer:/var/www/drzwidopiekna/
cp .env.production.example .env && chmod 600 .env
nano .env                                 # APP_URL, DB_PASSWORD, dane SMTP; reszta jest w komentarzach
composer install --no-dev --optimize-autoloader
php artisan key:generate --force

php artisan migrate --force
php artisan db:seed --force               # TYLKO RAZ, patrz uwaga poniżej
php artisan make:filament-user            # konto redaktorki
```

> **Uwaga: `db:seed` uruchamiaj tylko przy pierwszym wdrożeniu.** Seedery zabiegów, cennika i zespołu używają
> `updateOrCreate`, więc **drugie uruchomienie nadpisze zmiany zrobione w panelu**, a seeder cennika dodatkowo kasuje
> pozycje cennika i zakłada je od nowa. Seedery wpisów bloga i opinii (`firstOrCreate`) niczego nie nadpisują.
> Na produkcji seeder nie zakłada konta deweloperskiego ani przykładowej opinii. Szkielet polityki prywatności
> (`PageSeeder`) powstaje raz i niczego nie nadpisuje, więc w środowisku, które powstało wcześniej, wystarczy
> `php artisan db:seed --class=PageSeeder --force`.

Katalogi `storage` i `bootstrap/cache` muszą być zapisywalne dla PHP-FPM:

```bash
sudo chgrp -R www-data storage bootstrap/cache && sudo chmod -R g+rwX storage bootstrap/cache
```

Potem kolejne kroki robi skrypt (budowa frontu, `storage:link`, cache, restart SSR, test):

```bash
bash deploy/deploy.sh
```

## 4. nginx i HTTPS

Konfiguracja ma od razu docelowy układ: **domena główna bez www** (jak w adresach starej strony), `http` i `www`
przekierowują na nią jednym skokiem. Wymaga nginx w wersji z HTTP/2 w dyrektywie `listen` (każda z Ubuntu 22.04 i 24.04).
Gotowy plik odwołuje się do certyfikatu, którego jeszcze nie ma, więc **najpierw certyfikat, dopiero potem konfiguracja**
(certbot w trybie `standalone` potrzebuje wolnego portu 80, a DNS musi już wskazywać na serwer):

```bash
sudo systemctl stop nginx
sudo certbot certonly --standalone -d drzwidopiekna.pl -d www.drzwidopiekna.pl
sudo cp deploy/nginx/security-headers.conf /etc/nginx/snippets/ddp-security-headers.conf
sudo cp deploy/nginx/drzwidopiekna.conf /etc/nginx/sites-available/drzwidopiekna.conf
sudo ln -s /etc/nginx/sites-available/drzwidopiekna.conf /etc/nginx/sites-enabled/
sudo nginx -t        # popraw ścieżki (root, gniazdo PHP-FPM), jeśli zgłasza błąd
sudo systemctl start nginx
```

Odnowienia certyfikatu robi `certbot.timer`, ale tryb `standalone` przy odnawianiu też potrzebuje portu 80. Po wystawieniu certyfikatu
przestaw odnawianie na nginx: w `/etc/letsencrypt/renewal/drzwidopiekna.pl.conf` ustaw `authenticator = nginx` i `installer = nginx`
(sprawdzenie: `sudo certbot renew --dry-run`).

Certbot odnawia certyfikat sam (`systemctl list-timers | grep certbot`). Przy przełączeniu domeny patrz punkt 9.

**Przekierowań 301 ze starej strony nie ma w nginx.** Robi je aplikacja (`app/Http/Middleware/RedirectTrailingSlash.php` i
`config/clinic.php`), więc działają tak samo lokalnie, na stagingu i na produkcji, i mają testy.

## 5. Renderowanie po stronie serwera (SSR)

```bash
sudo cp deploy/supervisor/drzwidopiekna-ssr.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl status drzwidopiekna-ssr
bash deploy/check-ssr.sh https://drzwidopiekna.pl
```

Bez SSR strony działają w przeglądarce, ale roboty wyszukiwarek dostają pusty szkielet. Wpisz `check-ssr.sh` do monitoringu
albo crona (kod wyjścia 1 przy awarii). Proces słucha tylko na `127.0.0.1:13714`.

Konto `deploy` potrzebuje `sudo` bez hasła tylko dla dwóch poleceń użytych w `deploy.sh`
(`/etc/sudoers.d/drzwidopiekna`):

```
deploy ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart drzwidopiekna-ssr, /usr/bin/systemctl reload php8.4-fpm
```

## 6. Kolejne wdrożenia

```bash
cd /var/www/drzwidopiekna
git pull --ff-only          # albo rsync
bash deploy/deploy.sh
```

Skrypt sprawdza środowisko (`APP_ENV`, `APP_DEBUG`, rozszerzenia PHP, wersje PHP i Node), włącza ekran przerwy technicznej,
instaluje zależności, buduje front (klient i SSR), uruchamia migracje, buduje cache, przeładowuje PHP-FPM i SSR, wyłącza przerwę i testuje
`/up`, `/`, `/blog` oraz `/sitemap.xml`. Przerwa trwa zwykle poniżej minuty. Na stagingu: `EXPECT_ENV=staging bash deploy/deploy.sh`.

Zmiany w panelu (zabiegi, cennik, wpisy, zdjęcia) **nie wymagają wdrożenia**: są w bazie i w `storage`.

## 7. Kopie zapasowe

`deploy/backup.sh` zapisuje bazę (`pg_dump`, format custom) i zdjęcia z panelu, dostępne tylko dla właściciela (baza zawiera dane
pacjentek). Z crona, raz dziennie:

```
17 3 * * * cd /var/www/drzwidopiekna && bash deploy/backup.sh >> /var/log/drzwidopiekna/backup.log 2>&1
```

Kopia leży na tym samym serwerze, więc chroni przed błędem, a nie przed utratą serwera. **Wyślij ją poza serwer** (rclone,
rsync lub kopie migawkowe dostawcy VPS), najlepiej zaszyfrowaną. Wypróbuj odtwarzanie, zanim będzie potrzebne:

```bash
# baza (na pustej bazie testowej!)
createdb -O drzwi drzwi_odtwarzanie
pg_restore --no-owner --dbname drzwi_odtwarzanie /var/backups/drzwidopiekna/db-RRRRMMDD-GGMMSS.dump

# zdjęcia
tar -xzf /var/backups/drzwidopiekna/photos-RRRRMMDD-GGMMSS.tar.gz -C /var/www/drzwidopiekna/storage/app/public
```

## 8. Monitoring i logi

- **Dostępność:** zewnętrzny monitoring (np. UptimeRobot) na `https://drzwidopiekna.pl/up` (zwraca 200, gdy aplikacja działa).
- **SSR:** `deploy/check-ssr.sh` (punkt 5).
- **Logi aplikacji:** `storage/logs/laravel-RRRR-MM-DD.log` (rotacja dzienna, 14 dni, poziom `warning`).
  Zgłoszenia z formularza zapisują się w bazie także wtedy, gdy poczta zawiedzie, a błąd poczty ląduje w logu.
- **Logi nginx:** `/var/log/nginx/`. Błędy 404 pokazują, czego szukają ludzie i roboty:
  `awk '$9==404 {print $7}' /var/log/nginx/access.log | sort | uniq -c | sort -rn | head -30`
- **Search Console:** raport „Strony” i „Mapy witryn” po starcie, patrz punkt 9.

## 9. Przełączenie ze starej strony WordPress

Stara strona ma 70 adresów w mapach. Każdy z nich ma po przełączeniu przekierować (301) na działającą stronę nie później
niż po jednym skoku. Zestaw adresów jest w `tests/fixtures/old-site-paths.txt` i sprawdza go test automatyczny oraz skrypt na prawdziwym serwerze.

1. **Staging.** Wdróż na adres pomocniczy (np. `nowa.drzwidopiekna.pl`) z `APP_ENV=staging`, żeby `robots.txt` blokował
   indeksowanie. Sprawdź:
   ```bash
   bash deploy/check-redirects.sh https://nowa.drzwidopiekna.pl
   bash deploy/check-ssr.sh https://nowa.drzwidopiekna.pl
   ```
   Wyślij testowe zgłoszenie z formularza i sprawdź, czy mail dotarł (SPF i DKIM domeny, patrz `.env.production.example`).
   Zmierz Lighthouse (mobile) dla strony głównej, zabiegu i artykułu.
2. **Kopia starej strony.** Zrób eksport WordPressa (treści, `wp-content/uploads`, baza) i trzymaj go co najmniej 3 miesiące.
3. **DNS.** Dzień wcześniej obniż TTL rekordów A/AAAA do 300 s. W dniu przełączenia skieruj `drzwidopiekna.pl` i `www`
   na nowy serwer, potem `certbot --nginx` (albo wcześniej certyfikat metodą DNS-01, żeby nie było okna z ostrzeżeniem).
4. **Sprawdzenie na produkcji:**
   ```bash
   bash deploy/check-redirects.sh https://drzwidopiekna.pl
   bash deploy/check-ssr.sh https://drzwidopiekna.pl
   curl -s https://drzwidopiekna.pl/robots.txt        # musi zawierać Allow: / i Sitemap:
   ```
5. **Search Console:** dodaj usługę `https://drzwidopiekna.pl`, wyślij `https://drzwidopiekna.pl/sitemap.xml`, usuń stare mapy
   (`sitemap_index.xml` itd. same przekierowują na nową). Sprawdź w narzędziu do kontroli adresu kilka starych URL-i.
6. **Wizytówka Google, ZnanyLekarz, Facebook, Instagram:** sprawdź, że adres strony i dane (nazwa, adres, telefon) są identyczne.
7. **Przez 4 tygodnie** przeglądaj raport 404 (Search Console i logi nginx) i dopisuj brakujące przekierowania do
   `config/clinic.php` (`legacy_redirects`, `legacy_fallbacks`), a potem uruchom testy.

## 10. Wycofanie zmian

- **Błąd po wdrożeniu kodu:** wróć do poprzedniej wersji (`git checkout <poprzedni-commit>`) i uruchom `bash deploy/deploy.sh`.
  Migracje w tym projekcie są addytywne, więc zwykle nie trzeba ich cofać. Jeśli trzeba: `php artisan migrate:rollback --step=1`.
- **Awaria bazy:** odtwarzanie z kopii (punkt 7).
- **Problem po przełączeniu DNS:** stary serwer WordPress zostaje włączony do czasu, aż nowa strona zostanie sprawdzona;
  powrót to zmiana rekordów DNS (stąd niski TTL).

## 11. Bezpieczeństwo panelu `/admin`

- Konta zakłada się ręcznie (`php artisan make:filament-user`). Nie ma publicznej rejestracji.
- Logowanie ma limit prób. W produkcji `robots.txt` ukrywa `/admin` przed wyszukiwarkami.
- **Dwuskładnikowe logowanie (2FA) jest wymagane w produkcji.** Po `make:filament-user` konto loguje się hasłem i od razu trafia
  na ekran konfiguracji: skanuje kod QR aplikacją uwierzytelniającą (Google Authenticator, Microsoft Authenticator, Authy), wpisuje
  6-cyfrowy kod i hasło, a potem **zapisuje wyświetlone raz kody odzyskiwania w bezpiecznym miejscu** (8 kodów, każdy działa raz).
  Dalej każde logowanie wymaga kodu z aplikacji albo kodu odzyskiwania. Ustawienia: panel, avatar, Profil (można tam też wygenerować
  nowe kody odzyskiwania albo wyłączyć 2FA, gdy nie jest wymagane). Wymóg włącza się sam przy `APP_ENV=production`; `ADMIN_2FA_REQUIRED`
  wymusza go także gdzie indziej (np. staging), a `deploy.sh` nie pozwoli wdrożyć produkcji z `ADMIN_2FA_REQUIRED=false`.
- **Druga metoda: kod z e-maila.** W Profilu (sekcja dwuskładnikowego logowania) konto może włączyć też kod wysyłany na swój adres
  (6 cyfr, ważny 4 minuty, `QUEUE_CONNECTION=sync`, więc bez workera). Działa tylko przy skonfigurowanym SMTP (punkt „Poczta” w sekcji
  o stagingu). Konto z obiema metodami wybiera przy logowaniu jedną z nich. W Profilu można też włączyć **„wymagaj obu metod naraz”**
  (wyłączenie wymaga hasła): każde logowanie wymaga wtedy kodu z aplikacji i kodu z e-maila, który idzie od razu po hasle. Przy takim
  koncie awaria poczty oznacza brak dostępu do panelu, dopóki administrator serwera nie zresetuje 2FA (poniżej).
- **Utrata telefonu i kodów odzyskiwania albo awaria poczty przy „wymagaj obu”:** nikt z panelu tego nie naprawi (bez drugiego składnika
  nie da się zalogować), więc robi to administrator serwera: `php artisan admin:reset-2fa adres@konta.pl` (pyta o potwierdzenie, `--force`
  pomija pytanie; usuwa aplikację, kody awaryjne, kod z e-maila i „wymagaj obu”). Konto loguje się wtedy hasłem i konfiguruje 2FA od nowa.
  Przed resetem upewnij się, że o reset prosi właściwa osoba.
- Sekret 2FA i kody odzyskiwania są szyfrowane kluczem aplikacji (`APP_KEY`), kody dodatkowo skrótowane. **Zmiana `APP_KEY` unieważnia
  zapisane sekrety**, więc po niej każde konto trzeba zresetować i skonfigurować od nowa (kopie zapasowe bazy bez `.env` nie wystarczą).
- Dodatkowo można ograniczyć `/admin` do adresów IP gabinetu (zakomentowany blok na końcu `deploy/nginx/drzwidopiekna.conf`).
- Zgłoszenia pacjentek nie przechowują adresu IP. Nie ma jeszcze automatycznego usuwania starych zgłoszeń: okres
  przechowywania trzeba ustalić z klientem (RODO) i dopisać zadanie w harmonogramie.

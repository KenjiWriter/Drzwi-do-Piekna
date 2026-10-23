#!/usr/bin/env bash
#
# Pierwsza instalacja stagingu (demo) na Ubuntu 22.04 lub 24.04: nginx, PHP 8.4, PostgreSQL, Node, Supervisor (SSR),
# HTTPS z Let's Encrypt, pierwsze wdrożenie i dane startowe.
# Skrypt można uruchomić ponownie: pomija to, co już jest (nie nadpisuje .env, hasła bazy, konfiguracji nginx ani danych).
#
# Serwer współdzielony z inną aplikacją: skrypt dokłada własny blok nginx, własny pool PHP-FPM 8.4, własną rolę i bazę w PostgreSQL
# i własny proces w Supervisorze. Nie przestawia domyślnego `php` ani istniejącego Node, nie rusza cudzych witryn nginx.
# Zmienia jednak wspólne rzeczy: instaluje pakiety php8.4-*, a `apt` może zaktualizować już zainstalowane pakiety php8.4-common i
# php8.4-mbstring. Gdy Node nie ma, instaluje 22 LTS.
#
#   sudo apt update && sudo apt install -y git
#   sudo git clone https://github.com/KenjiWriter/Drzwi-do-Piekna.git /var/www/drzwidopiekna
#   sudo LE_EMAIL=ty@example.com bash /var/www/drzwidopiekna/deploy/provision-staging.sh
#
# Zmienne:
#   LE_EMAIL    (wymagana) adres dla Let's Encrypt, trafiają tam tylko ostrzeżenia o wygasającym certyfikacie
#   DOMAIN      domena stagingu (domyślnie demo.cerasusdigital.pl). Rekord A musi już wskazywać na ten serwer
#   BRANCH      gałąź do wdrożenia (domyślnie main)
#   ADMIN_2FA   czy panel /admin wymaga 2FA: true (domyślnie) albo false (wygodniej na pokazie, mniej bezpiecznie)
#
# Środowisko to APP_ENV=staging: robots.txt blokuje indeksowanie, dokumenty prawne nie blokują wdrożenia,
# poczta zapisuje wiadomości do logu (MAIL_MAILER=log) do czasu podania SMTP w .env.
# Produkcja (drzwidopiekna.pl) to osobna instrukcja: docs/DEPLOY.md.
#
set -euo pipefail

DOMAIN="${DOMAIN:-demo.cerasusdigital.pl}"
LE_EMAIL="${LE_EMAIL:-}"
BRANCH="${BRANCH:-main}"
ADMIN_2FA="${ADMIN_2FA:-true}"
REPO="${REPO:-https://github.com/KenjiWriter/Drzwi-do-Piekna.git}"
# Ścieżka jest wpisana na stałe w deploy/nginx/*.conf i deploy/supervisor/*.conf.
APP_DIR=/var/www/drzwidopiekna
LOG_DIR=/var/log/drzwidopiekna
DB_NAME=drzwi_do_piekna
DB_USER=drzwi

export DEBIAN_FRONTEND=noninteractive
export COMPOSER_ALLOW_SUPERUSER=1

say() { printf '\n\033[1m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[33mUWAGA: %s\033[0m\n' "$*" >&2; }
fail() { printf '\033[31mBŁĄD: %s\033[0m\n' "$*" >&2; exit 1; }
pg() { (cd / && runuser -u postgres -- "$@"); }
set_env() {
    local key="$1" value="$2"
    if grep -qE "^${key}=" "$APP_DIR/.env"; then
        sed -i "s|^${key}=.*|${key}=${value}|" "$APP_DIR/.env"
    else
        printf '%s=%s\n' "$key" "$value" >> "$APP_DIR/.env"
    fi
}

say "Sprawdzanie serwera"
[ "$(id -u)" = "0" ] || fail "uruchom jako root (sudo bash $0)"
[ -n "$LE_EMAIL" ] || fail "podaj adres e-mail dla Let's Encrypt: sudo LE_EMAIL=ty@example.com bash $0"
# shellcheck disable=SC1091
. /etc/os-release
[ "${ID:-}" = "ubuntu" ] || fail "skrypt jest pisany pod Ubuntu (jest: ${PRETTY_NAME:-nieznany system})"
case "${VERSION_ID:-}" in 22.04|24.04) ;; *) warn "testowane na Ubuntu 22.04 i 24.04, jest ${VERSION_ID:-?}";; esac

resolved="$(getent ahostsv4 "$DOMAIN" | awk 'NR==1 {print $1}' || true)"
own_ip="$(curl -4 -fsS --max-time 10 https://api.ipify.org 2>/dev/null || true)"
[ -n "$resolved" ] || fail "domena $DOMAIN nie ma rekordu A. Dodaj go w DNS (adres tego serwera) i poczekaj na propagację"
if [ -n "$own_ip" ] && [ "$resolved" != "$own_ip" ]; then
    fail "domena $DOMAIN wskazuje na $resolved, a ten serwer ma adres $own_ip. Popraw rekord A, inaczej Let's Encrypt nie wystawi certyfikatu"
fi
echo "  $DOMAIN -> ${resolved} (zgodne z tym serwerem)"

mem_mb="$(awk '/MemTotal/ {print int($2/1024)}' /proc/meminfo)"
swap_mb="$(awk '/SwapTotal/ {print int($2/1024)}' /proc/meminfo)"
if [ "$mem_mb" -lt 1800 ] && [ "$swap_mb" -lt 1024 ] && [ ! -e /swapfile ]; then
    say "Mało pamięci (${mem_mb} MB RAM, brak swapu): dodaję 2 GB swapu na czas budowy frontu"
    fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile >/dev/null && swapon /swapfile
    grep -q '^/swapfile ' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

say "Pakiety systemowe"
apt-get update -y
apt-get install -y nginx postgresql supervisor certbot python3-certbot-nginx git unzip curl ca-certificates software-properties-common openssl

if ! apt-cache policy php8.4-fpm 2>/dev/null | grep 'Candidate: [0-9]' >/dev/null; then
    say "Repozytorium PHP (ppa:ondrej/php)"
    add-apt-repository -y ppa:ondrej/php
    apt-get update -y
fi
say "PHP 8.4 i rozszerzenia"
apt-get install -y php8.4-fpm php8.4-cli php8.4-pgsql php8.4-gd php8.4-intl php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip

cat > /etc/php/8.4/fpm/conf.d/99-drzwidopiekna.ini <<'INI'
expose_php = Off
memory_limit = 256M
upload_max_filesize = 12M
post_max_size = 14M
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
; Bez sprawdzania znaczników czasu plików: po każdym wdrożeniu PHP-FPM jest przeładowywany (robi to deploy.sh).
opcache.validate_timestamps = 0
INI

# Node instalujemy tylko, gdy go nie ma. Istniejącego nie podnosimy (na serwerze współdzielonym korzystają z niego inne aplikacje).
# Wymagania Vite 8 i jego wtyczek: Node ^20.19 albo >=22.12.
node_ok() { node -e 'const [a, b] = process.versions.node.split(".").map(Number); process.exit((a === 20 && b >= 19) || (a === 22 && b >= 12) || a >= 23 ? 0 : 1)' 2>/dev/null; }
if ! command -v node >/dev/null; then
    say "Node.js 22 LTS"
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y nodejs
elif ! node_ok; then
    fail "Node $(node -v) jest za stary dla Vite 8 (potrzebny 20.19+ albo 22.12+). Skrypt nie podnosi go sam, bo korzystają z niego inne aplikacje: zaktualizuj Node ręcznie"
fi

if ! command -v composer >/dev/null; then
    say "Composer"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

systemctl enable --now postgresql php8.4-fpm nginx supervisor

say "Kod aplikacji ($REPO, gałąź $BRANCH)"
mkdir -p "$LOG_DIR" "$(dirname "$APP_DIR")"
chown www-data:www-data "$LOG_DIR"
if [ -d "$APP_DIR/.git" ]; then
    git -C "$APP_DIR" fetch origin "$BRANCH"
    git -C "$APP_DIR" checkout "$BRANCH"
    git -C "$APP_DIR" pull --ff-only origin "$BRANCH"
else
    [ ! -e "$APP_DIR" ] || [ -z "$(ls -A "$APP_DIR")" ] || fail "katalog $APP_DIR istnieje i nie jest pusty ani repozytorium git"
    git clone --branch "$BRANCH" "$REPO" "$APP_DIR"
fi
cd "$APP_DIR"

# Gdy domyślne `php` jest starsze niż 8.4 (inna aplikacja na tym serwerze), używamy php8.4 obok niego, bez zmiany systemu.
# shellcheck disable=SC1091
. deploy/use-php84.sh

if [ ! -f .env ]; then
    say "Baza PostgreSQL i plik .env"
    db_password="$(openssl rand -hex 24)"
    if [ "$(pg psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='$DB_USER'")" = "1" ]; then
        pg psql -qc "ALTER ROLE $DB_USER PASSWORD '$db_password'"
    else
        pg psql -qc "CREATE ROLE $DB_USER LOGIN PASSWORD '$db_password'"
    fi
    [ "$(pg psql -tAc "SELECT 1 FROM pg_database WHERE datname='$DB_NAME'")" = "1" ] || pg createdb -O "$DB_USER" -E UTF8 "$DB_NAME"

    cp .env.production.example .env
    chmod 600 .env
    set_env APP_ENV staging
    set_env APP_URL "https://$DOMAIN"
    set_env DB_PASSWORD "$db_password"
    set_env ADMIN_2FA_REQUIRED "$ADMIN_2FA"
    # Dopóki nie ma SMTP, wiadomości (potwierdzenie dla klientki, powiadomienie dla gabinetu) trafiają do storage/logs.
    set_env MAIL_MAILER log
    echo "  utworzono .env (hasło bazy wygenerowane, zapisane tylko w .env)"
else
    echo "  .env już istnieje, nie ruszam"
fi

say "Zależności PHP i klucz aplikacji"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
grep -qE '^APP_KEY=.+' .env || php artisan key:generate --force

# Migracje i dane startowe jeszcze przed deploy.sh, żeby jego test po wdrożeniu trafił na stronę z treścią.
say "Baza: migracje i dane startowe"
php artisan migrate --force
treatments="$(pg psql -d "$DB_NAME" -tAc 'SELECT count(*) FROM treatments' 2>/dev/null || echo 0)"
if [ "${treatments:-0}" = "0" ]; then
    # Tylko za pierwszym razem: seedery zabiegów, cennika i zespołu nadpisałyby zmiany zrobione w panelu.
    php artisan db:seed --force
    php artisan photos:import-demo
else
    echo "  baza ma już dane, pomijam db:seed"
fi
chown -R www-data:www-data storage bootstrap/cache

say "nginx"
install -m 644 deploy/nginx/security-headers.conf /etc/nginx/snippets/ddp-security-headers.conf
# Na stagingu HSTS na 5 minut, żeby pomyłka w konfiguracji HTTPS nie zostawała w przeglądarkach na rok.
sed -i 's/max-age=31536000/max-age=300/' /etc/nginx/snippets/ddp-security-headers.conf
site="/etc/nginx/sites-available/$DOMAIN.conf"
if [ ! -f "$site" ]; then
    sed "s/demo\.cerasusdigital\.pl/$DOMAIN/g" deploy/nginx/staging.conf > "$site"
fi
ln -sf "$site" "/etc/nginx/sites-enabled/$DOMAIN.conf"
nginx -t
systemctl reload nginx

if command -v ufw >/dev/null && ufw status | grep 'Status: active' >/dev/null; then
    say "Zapora (ufw jest aktywna): otwieram HTTP i HTTPS"
    ufw allow 'Nginx Full'
fi

if [ ! -d "/etc/letsencrypt/live/$DOMAIN" ]; then
    say "Certyfikat HTTPS (Let's Encrypt)"
    certbot --nginx -d "$DOMAIN" -m "$LE_EMAIL" --agree-tos --no-eff-email --redirect --non-interactive
fi

say "Pierwsze wdrożenie (deploy/deploy.sh: front, migracje, cache)"
# SSR_SERVICE="" pomija restart SSR (Supervisor dostaje konfigurację dopiero niżej), SUDO="" bo działamy jako root.
EXPECT_ENV=staging SSR_SERVICE="" SUDO="" bash deploy/deploy.sh

say "Renderowanie po stronie serwera (Supervisor)"
install -m 644 deploy/supervisor/drzwidopiekna-ssr.conf /etc/supervisor/conf.d/drzwidopiekna-ssr.conf
supervisorctl reread
supervisorctl update
sleep 8
supervisorctl status drzwidopiekna-ssr
bash deploy/check-ssr.sh "https://$DOMAIN" || warn "SSR nie odpowiada: sprawdź $LOG_DIR/ssr.log"

cat <<KONIEC

$(printf '\033[32m')Staging działa: https://$DOMAIN$(printf '\033[0m')

Zostały dwie rzeczy, które robisz ręcznie:

  1. Konto do panelu /admin (hasło wpisujesz sam, nie trafia do historii poleceń):
       cd $APP_DIR && php8.4 artisan make:filament-user
     Panel: https://$DOMAIN/admin$( [ "$ADMIN_2FA" = "true" ] && echo ". Po pierwszym logowaniu panel poprosi o skonfigurowanie 2FA (aplikacja uwierzytelniająca)." )

  2. Poczta: do czasu podania SMTP w $APP_DIR/.env (MAIL_MAILER, MAIL_HOST, ...) wiadomości z formularza zapisują się w
     $APP_DIR/storage/logs, a nie są wysyłane. Po zmianie .env: php8.4 artisan config:cache && systemctl reload php8.4-fpm
     (NIE samo config:clear: .env jest nieczytelny dla www-data, aplikacja działa z zapisanej konfiguracji).

Kolejne wdrożenia (pobranie z gita, lista zmian, deploy): cd $APP_DIR && sudo bash deploy/update.sh
KONIEC

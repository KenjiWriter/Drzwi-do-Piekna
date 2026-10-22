#!/usr/bin/env bash
#
# Wdrożenie na serwerze produkcyjnym (w miejscu, z krótką przerwą 503).
#
#   bash deploy/deploy.sh
#
# Skrypt zakłada, że kod aplikacji już leży w katalogu projektu (git pull albo rsync, patrz docs/DEPLOY.md).
# Zmienne (wszystkie opcjonalne):
#   EXPECT_ENV       oczekiwana wartość APP_ENV (domyślnie production; na stagingu: staging, wtedy robots.txt
#                    blokuje indeksowanie)
#   ALLOW_DRAFT_LEGAL=1  pozwala wdrożyć produkcję mimo niegotowych dokumentów prawnych (php artisan legal:check);
#                    domyślnie takie wdrożenie jest blokowane
#   SSR_SERVICE      nazwa programu w Supervisorze (domyślnie drzwidopiekna-ssr), "" pomija restart
#   PHP_FPM_SERVICE  usługa PHP-FPM do przeładowania OPcache (domyślnie php8.4-fpm), "" pomija
#   SMOKE_URL        adres do testu po wdrożeniu (domyślnie APP_URL z .env)
#   SUDO             polecenie podnoszące uprawnienia dla supervisorctl i systemctl (domyślnie sudo)
#
set -euo pipefail

cd "$(dirname "$0")/.."

EXPECT_ENV="${EXPECT_ENV:-production}"
SSR_SERVICE="${SSR_SERVICE-drzwidopiekna-ssr}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE-php8.4-fpm}"
SUDO="${SUDO-sudo}"

say() { printf '\n\033[1m==> %s\033[0m\n' "$*"; }
fail() { printf '\033[31mBŁĄD: %s\033[0m\n' "$*" >&2; exit 1; }
env_value() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }

say "Sprawdzanie środowiska"
[ -f .env ] || fail "brak pliku .env (wzór: .env.production.example)"
[ "$(env_value APP_ENV)" = "$EXPECT_ENV" ] || fail "APP_ENV w .env musi mieć wartość $EXPECT_ENV"
[ "$(env_value APP_DEBUG)" = "false" ] || fail "APP_DEBUG w .env musi mieć wartość false"
[ -n "$(env_value APP_KEY)" ] || fail "brak APP_KEY (php artisan key:generate --force)"
case "$(env_value APP_URL)" in https://*) ;; *) fail "APP_URL musi zaczynać się od https://";; esac
[ "$(env_value DB_CONNECTION)" = "pgsql" ] || fail "w produkcji baza to PostgreSQL (DB_CONNECTION=pgsql)"
[ -z "$(env_value ADMIN_PASSWORD)" ] || fail "ADMIN_PASSWORD nie może być ustawione w produkcji"
if [ "$EXPECT_ENV" = "production" ]; then
    case "$(env_value ADMIN_2FA_REQUIRED | tr '[:upper:]' '[:lower:]')" in false|0|off|no) fail "ADMIN_2FA_REQUIRED nie może być wyłączone w produkcji (dwuskładnikowe logowanie do panelu jest wymagane)";; esac
fi

php -r 'exit(PHP_VERSION_ID >= 80400 ? 0 : 1);' || fail "wymagany PHP 8.4 lub nowszy"
for ext in gd exif intl mbstring pdo_pgsql openssl curl dom fileinfo; do
    php -m | grep -qix "$ext" || fail "brak rozszerzenia PHP: $ext"
done
php -r 'exit(function_exists("imagewebp") ? 0 : 1);' || fail "rozszerzenie gd bez obsługi WebP (potrzebna do zdjęć z panelu)"
command -v node >/dev/null || fail "brak Node.js (potrzebny do budowy i do SSR)"
node -e 'process.exit(Number(process.versions.node.split(".")[0]) >= 22 ? 0 : 1)' || fail "wymagany Node.js 22 lub nowszy"

if [ "$EXPECT_ENV" = "production" ]; then
    say "Dokumenty prawne (polityka prywatności, zgoda w formularzu)"
    if ! php artisan legal:check; then
        [ "${ALLOW_DRAFT_LEGAL:-0}" = "1" ] || fail "dokumenty prawne nie są gotowe (lista wyżej). Uzupełnij je w panelu (Treści, Dokumenty) i w lang/pl/pages.php albo świadomie pomiń: ALLOW_DRAFT_LEGAL=1 bash deploy/deploy.sh"
        printf '\033[33mUWAGA: wdrażasz z niegotowymi dokumentami prawnymi (ALLOW_DRAFT_LEGAL=1).\033[0m\n'
    fi
fi

say "Tryb przerwy technicznej (ekran 503)"
php artisan down --retry=60 --refresh=30 || true

say "Zależności PHP"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

say "Zależności i budowa frontu (klient + SSR)"
npm ci
npm run build
[ -f bootstrap/ssr/app.js ] || fail "po budowie brak bootstrap/ssr/app.js (bundle SSR)"

say "Migracje bazy"
php artisan migrate --force

say "Dowiązanie zdjęć z panelu (public/storage)"
[ -L public/storage ] || php artisan storage:link

say "Cache konfiguracji, tras, widoków i zdarzeń"
php artisan optimize

# Uruchomione jako root (np. provision-staging.sh) tworzy pliki w storage i bootstrap/cache jako root,
# a PHP-FPM pracuje jako www-data i musi tam pisać (sesje, logi, skompilowane widoki, zdjęcia z panelu).
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi

if [ -n "$PHP_FPM_SERVICE" ]; then
    say "Przeładowanie PHP-FPM (czyszczenie OPcache)"
    $SUDO systemctl reload "$PHP_FPM_SERVICE"
fi

if [ -n "$SSR_SERVICE" ]; then
    say "Restart procesu SSR"
    $SUDO supervisorctl restart "$SSR_SERVICE"
fi

say "Koniec przerwy technicznej"
php artisan up

SMOKE_URL="${SMOKE_URL:-$(env_value APP_URL)}"
say "Test po wdrożeniu: $SMOKE_URL"
sleep 3
for path in /up / /blog /sitemap.xml; do
    code=$(curl -s -o /dev/null -w '%{http_code}' "${SMOKE_URL%/}$path" || true)
    printf '  %-14s %s\n' "$path" "$code"
    [ "$code" = "200" ] || fail "adres $path zwrócił $code, a powinien 200"
done

printf '\n\033[32mWdrożenie zakończone.\033[0m Sprawdź jeszcze: bash deploy/check-ssr.sh %s\n' "$SMOKE_URL"

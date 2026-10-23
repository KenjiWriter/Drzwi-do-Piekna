#!/usr/bin/env bash
#
# Aktualizacja wdrożonej aplikacji do wersji z gita jednym poleceniem: pobiera zmiany, pokazuje, co się zmieni
# (commity, migracje, zależności, zmiany w szablonie .env i w konfiguracji serwera), przesuwa kod i uruchamia deploy/deploy.sh.
#
#   sudo bash deploy/update.sh                  # pobierz, pokaż zmiany, wdróż
#   sudo bash deploy/update.sh --dry-run        # tylko pokaż, co się zmieni, nic nie wdrażaj
#   sudo bash deploy/update.sh --force          # wdróż także, gdy kod się nie zmienił (np. po zmianie .env)
#   sudo bash deploy/update.sh --to <commit>    # wdróż wskazany commit lub tag (powrót do starszej wersji)
#
# Zmienne (opcjonalne):
#   BRANCH      gałąź do śledzenia (domyślnie main)
#   EXPECT_ENV  środowisko przekazywane do deploy.sh (domyślnie APP_ENV z pliku .env, więc staging zostaje stagingiem)
#   SUDO        jak w deploy.sh; jako root ustawiane na "" samo
#   DEPLOY_SCRIPT  skrypt wdrożenia (domyślnie deploy/deploy.sh)
#
# Skrypt odmawia, gdy w katalogu są lokalne zmiany w plikach śledzonych przez git (zrób `git status`; odrzucenie:
# `git reset --hard`, nie rusza .env ani storage). Nie stosuje zmian w konfiguracji nginx i Supervisora: skrypt tylko ostrzeże,
# że się zmieniły (patrz docs/DEPLOY.md).
#
set -euo pipefail

# Cała logika jest w funkcji, a plik czytany do końca zanim cokolwiek się wykona: `git merge` podmienia ten skrypt (i deploy.sh)
# na dysku, a bash czyta skrypt przyrostowo, więc bez tego mógłby wykonać mieszankę starej i nowej wersji.
main() {
    cd "$(dirname "$0")/.."

    local BRANCH="${BRANCH:-main}" deploy_script="${DEPLOY_SCRIPT:-deploy/deploy.sh}" dry_run=0 force=0 to=""

    while [ $# -gt 0 ]; do
        case "$1" in
            --dry-run) dry_run=1 ;;
            --force) force=1 ;;
            --to) shift; to="${1:?podaj commit albo tag po --to}" ;;
            -h|--help) awk 'NR > 1 && /^#/ { sub(/^# ?/, ""); print; next } NR > 1 { exit }' "$0"; return 0 ;;
            *) fail "nieznana opcja: $1 (pomoc: --help)" ;;
        esac
        shift
    done

    git rev-parse --is-inside-work-tree >/dev/null 2>&1 || fail "to nie jest repozytorium git (klon: git clone <adres> .)"
    [ -f .env ] || fail "brak pliku .env"

    local expect_env
    expect_env="${EXPECT_ENV:-$(grep -E '^APP_ENV=' .env | head -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//')}"
    [ -n "$expect_env" ] || fail "nie ustalono środowiska (brak APP_ENV w .env)"

    if [ -z "${SUDO+x}" ] && [ "$(id -u)" = "0" ]; then
        export SUDO=""
    fi

    if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
        git status --short --untracked-files=no >&2
        fail "w katalogu są lokalne zmiany w plikach śledzonych przez git (lista wyżej). Odrzuć je: git reset --hard (nie rusza .env ani storage)"
    fi

    say "Pobieranie zmian z gita (gałąź $BRANCH)"
    git fetch --quiet --tags origin "$BRANCH"

    local head target target_ref
    head="$(git rev-parse HEAD)"
    if [ -n "$to" ]; then
        target_ref="$to"
    else
        target_ref="origin/$BRANCH"
    fi
    target="$(git rev-parse --verify --quiet "${target_ref}^{commit}")" || fail "nie znaleziono wersji: $target_ref"

    printf '  teraz:   %s\n  docelowo: %s (%s)\n' "$(git log -1 --format='%h %s' "$head")" "$(git log -1 --format='%h %s' "$target")" "$target_ref"

    if [ "$head" = "$target" ] && [ "$force" = "0" ]; then
        echo
        echo "Kod jest już aktualny. Wdrożyć mimo to (np. po zmianie .env): sudo bash deploy/update.sh --force"
        return 0
    fi

    if [ "$head" != "$target" ]; then
        show_changes "$head" "$target"
    fi

    if [ "$dry_run" = "1" ]; then
        echo
        echo "Tryb --dry-run: nic nie zmieniono."
        return 0
    fi

    if [ "$head" != "$target" ]; then
        say "Przesuwanie kodu"
        if [ -n "$to" ]; then
            git checkout --quiet --detach "$target"
        else
            # Po wdrożeniu starszej wersji (--to) HEAD jest odłączony: wróć na gałąź, potem tylko szybkie przesunięcie.
            git symbolic-ref -q HEAD >/dev/null || git checkout --quiet "$BRANCH"
            git merge --ff-only "$target_ref" >/dev/null || fail "gałąź lokalna rozjechała się z origin/$BRANCH (git status). Jeśli nie ma tu nic ważnego: git reset --hard origin/$BRANCH"
        fi
    fi

    say "Wdrożenie ($deploy_script, środowisko: $expect_env)"
    EXPECT_ENV="$expect_env" bash "$deploy_script"

    printf '\n\033[32mZaktualizowano do %s.\033[0m\n' "$(git log -1 --format='%h %s' HEAD)"
    if [ "$head" != "$target" ]; then
        printf 'Poprzednia wersja: %s. Powrót do niej: sudo bash deploy/update.sh --to %s\n' "$(git rev-parse --short "$head")" "$(git rev-parse --short "$head")"
    fi
}

say() { printf '\n\033[1m==> %s\033[0m\n' "$*"; }
fail() { printf '\033[31mBŁĄD: %s\033[0m\n' "$*" >&2; exit 1; }

show_changes() {
    local from="$1" to="$2" direction files migrations

    if git merge-base --is-ancestor "$from" "$to"; then
        direction="forward"
        say "Nowe commity"
        git log --oneline --no-decorate -n 30 "$from..$to"
    elif git merge-base --is-ancestor "$to" "$from"; then
        direction="back"
        say "Cofasz wersję. Commity, które znikną z wdrożenia"
        git log --oneline --no-decorate -n 30 "$to..$from"
    else
        direction="diverged"
        say "Wersje mają różne historie"
    fi

    files="$(git diff --name-only "$from" "$to")"

    say "Zmiany ($(git diff --shortstat "$from" "$to" | sed 's/^ //'))"
    migrations="$(grep -c '^database/migrations/' <<<"$files" || true)"
    [ "$migrations" -gt 0 ] && echo "  migracje bazy: $migrations plik(ów), wykonają się automatycznie (migrate --force)"
    grep -q '^composer.lock$' <<<"$files" && echo "  zależności PHP: composer.lock się zmienił (composer install)"
    grep -q '^package-lock.json$' <<<"$files" && echo "  zależności JS: package-lock.json się zmienił (npm ci)"
    if grep -qE '^\.env(\.production)?\.example$' <<<"$files"; then
        if [ "$direction" = "back" ]; then
            echo "  .env: szablon środowiska się zmienił, ale starsza wersja nie wymaga niczego nowego"
        else
            echo "  UWAGA .env: zmienił się szablon środowiska. Dodane linie (sprawdź, czy .env ma te zmienne):"
            git diff "$from" "$to" -- .env.example .env.production.example | { grep -E '^\+[^+]' || true; } | head -20 | sed 's/^+/    /'
        fi
    fi
    if grep -qE '^deploy/(nginx|supervisor)/' <<<"$files"; then
        echo "  UWAGA serwer: zmieniły się pliki konfiguracji nginx lub Supervisora. deploy.sh ich nie stosuje, skopiuj je ręcznie (docs/DEPLOY.md)"
    fi
    if [ "$direction" = "back" ] && [ "$migrations" -gt 0 ]; then
        echo "  UWAGA: cofasz kod, ale migracje zostają w bazie (są dodające, zwykle nie przeszkadza; cofnięcie: php artisan migrate:rollback --step=N)"
    fi

    return 0
}

main "$@"
exit $?

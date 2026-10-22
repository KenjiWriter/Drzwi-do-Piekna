# Wczytywany przez deploy.sh i provision-staging.sh (source), nie uruchamiany samodzielnie.
#
# Aplikacja wymaga PHP 8.4. Na serwerze współdzielonym z inną aplikacją domyślne `php` może być starsze (np. 8.3) i nie wolno go przestawiać.
# Wtedy na czas działania skryptu katalog tymczasowy z dowiązaniem `php` -> php8.4 trafia na początek PATH. Obejmuje to też `composer`
# (jego shebang to `env php`) i polecenia `php artisan`. Domyślne `php` w systemie zostaje bez zmian.
if ! php -r 'exit(PHP_VERSION_ID >= 80400 ? 0 : 1);' 2>/dev/null && command -v php8.4 >/dev/null 2>&1; then
    DDP_PHP_SHIM="$(mktemp -d)"
    ln -s "$(command -v php8.4)" "$DDP_PHP_SHIM/php"
    export PATH="$DDP_PHP_SHIM:$PATH"
    trap 'rm -rf "$DDP_PHP_SHIM"' EXIT
    echo "  Domyślne php w systemie jest starsze niż 8.4, dla tej aplikacji używam $(command -v php8.4) (system bez zmian)"
fi

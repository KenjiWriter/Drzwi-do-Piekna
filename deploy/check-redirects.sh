#!/usr/bin/env bash
#
# Sprawdza przekierowania 301 ze starej strony WordPress na działającym serwerze (staging albo produkcja):
# każdy adres z tests/fixtures/old-site-paths.txt ma odpowiedzieć 301 (albo 200 dla adresów, które istnieją
# także w nowej stronie), a miejsce docelowe 200, bez drugiego przekierowania.
#
#   bash deploy/check-redirects.sh https://drzwidopiekna.pl
#
# Ten sam zestaw adresów sprawdza test automatyczny (tests/Feature/LegacyUrlsTest.php), ale tu widać
# prawdziwy serwer razem z nginx, HTTPS i domeną. Kod wyjścia 1 oznacza co najmniej jeden błąd.
#
set -uo pipefail

cd "$(dirname "$0")/.."

BASE="${1:?Podaj adres serwera, np. https://drzwidopiekna.pl}"
BASE="${BASE%/}"
FILE="tests/fixtures/old-site-paths.txt"
errors=0

printf '%-70s %-5s %s\n' "STARY ADRES" "KOD" "DOKĄD"

while IFS= read -r path; do
    [ -n "$path" ] || continue

    read -r code location < <(curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' "$BASE$path")
    location="${location%$'\r'}" # curl.exe na Windowsie dopisuje \r

    if [ "$code" = "301" ]; then
        target="${location#"$BASE"}"
        # Adres z fragmentem (#zespol) sprawdzamy bez fragmentu, bo przeglądarka go nie wysyła.
        final=$(curl -s -o /dev/null -w '%{http_code}' "$BASE${target%%#*}")
        status="ok"; [ "$final" = "200" ] || { status="BŁĄD: cel zwraca $final"; errors=$((errors + 1)); }
        printf '%-70s %-5s %s  %s\n' "$path" "$code" "$target" "$status"
    elif [ "$code" = "200" ] && [ "$path" = "/" -o "${path: -1}" != "/" ]; then
        printf '%-70s %-5s %s\n' "$path" "$code" "(adres istnieje w nowej stronie)"
    else
        printf '%-70s %-5s %s\n' "$path" "$code" "BŁĄD: oczekiwano 301"
        errors=$((errors + 1))
    fi
done < "$FILE"

echo
if [ "$errors" -eq 0 ]; then
    echo "Wszystkie stare adresy kończą na działającej stronie."
else
    echo "Błędów: $errors" >&2
    exit 1
fi

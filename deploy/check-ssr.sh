#!/usr/bin/env bash
#
# Sprawdza, czy działa renderowanie po stronie serwera (SSR). Bez niego strony wyglądają normalnie w przeglądarce,
# ale roboty wyszukiwarek dostają pusty szkielet bez treści (tytuł i dane strukturalne nadal są, bo wypisuje je Blade).
# Test: pierwsza odpowiedź HTML musi już zawierać nagłówek h1 ze strony.
#
#   bash deploy/check-ssr.sh https://drzwidopiekna.pl
#
# Nadaje się do crona lub do monitoringu (kod wyjścia 1 przy awarii).
#
set -uo pipefail

BASE="${1:?Podaj adres serwera, np. https://drzwidopiekna.pl}"
BASE="${BASE%/}"
failed=0

for path in / /zabiegi /blog /kontakt; do
    if curl -fsS --max-time 20 "$BASE$path" | grep -q '<h1'; then
        printf '  %-10s SSR działa\n' "$path"
    else
        printf '  %-10s BRAK SSR (w HTML nie ma h1)\n' "$path" >&2
        failed=1
    fi
done

if [ "$failed" -ne 0 ]; then
    echo "Uruchom ponownie proces: sudo supervisorctl restart drzwidopiekna-ssr (logi: /var/log/drzwidopiekna/ssr.log)" >&2
    exit 1
fi

#!/usr/bin/env bash
#
# Kopia zapasowa: baza PostgreSQL i zdjęcia wgrane w panelu. Uruchamiaj z crona raz dziennie:
#
#   17 3 * * * cd /var/www/drzwidopiekna && bash deploy/backup.sh >> /var/log/drzwidopiekna/backup.log 2>&1
#
# Baza zawiera dane pacjentek (zgłoszenia z formularza), więc kopie są dostępne tylko dla właściciela (umask 077).
# Ta kopia leży na tym samym serwerze: to ochrona przed błędem, nie przed utratą serwera. Przenieś ją poza serwer
# (rclone, rsync, kopia dostawcy VPS), najlepiej zaszyfrowaną. Odtwarzanie: docs/DEPLOY.md.
#
#   BACKUP_DIR   gdzie zapisywać (domyślnie /var/backups/drzwidopiekna)
#   KEEP_DAYS    ile dni trzymać (domyślnie 30)
#
set -euo pipefail
umask 077

cd "$(dirname "$0")/.."

BACKUP_DIR="${BACKUP_DIR:-/var/backups/drzwidopiekna}"
KEEP_DAYS="${KEEP_DAYS:-30}"
STAMP="$(date +%Y%m%d-%H%M%S)"

env_value() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }

[ "$(env_value DB_CONNECTION)" = "pgsql" ] || { echo "Skrypt obsługuje tylko PostgreSQL (DB_CONNECTION=pgsql)" >&2; exit 1; }

mkdir -p "$BACKUP_DIR"

echo "[$(date -Is)] baza"
PGPASSWORD="$(env_value DB_PASSWORD)" pg_dump \
    --host "$(env_value DB_HOST)" --port "$(env_value DB_PORT)" --username "$(env_value DB_USERNAME)" \
    --format=custom --no-owner --file "$BACKUP_DIR/db-$STAMP.dump" "$(env_value DB_DATABASE)"

echo "[$(date -Is)] zdjęcia"
if [ -d storage/app/public/photos ]; then
    tar -czf "$BACKUP_DIR/photos-$STAMP.tar.gz" -C storage/app/public photos
fi

echo "[$(date -Is)] sprzątanie kopii starszych niż $KEEP_DAYS dni"
find "$BACKUP_DIR" -maxdepth 1 -type f \( -name 'db-*.dump' -o -name 'photos-*.tar.gz' \) -mtime +"$KEEP_DAYS" -delete

echo "[$(date -Is)] gotowe: $(ls -1 "$BACKUP_DIR" | wc -l) plików w $BACKUP_DIR"

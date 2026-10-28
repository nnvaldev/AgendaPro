#!/usr/bin/env bash
# Backup de PostgreSQL en formato custom (pg_restore). Lo programa deploy.sh
# en cron todos los días a las 04:15 y conserva los últimos KEEP_DAYS días.
#
# Restaurar:
#   docker compose -f compose.prod.yaml --env-file .env.production --env-file .env.deployed exec -T postgres \
#     sh -c 'pg_restore -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists' < backups/<archivo>.dump
set -euo pipefail
cd "$(dirname "$0")"

KEEP_DAYS="${KEEP_DAYS:-14}"
mkdir -p backups

file="backups/agendapro-$(date -u +%Y%m%d-%H%M%S).dump"

# Se escribe a un .partial y se renombra al terminar: un backup cortado a la
# mitad nunca queda con el nombre de uno válido.
docker compose -f compose.prod.yaml --env-file .env.production --env-file .env.deployed exec -T postgres \
    sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --format=custom' > "$file.partial"
mv "$file.partial" "$file"

find backups -name 'agendapro-*.dump' -mtime +"$KEEP_DAYS" -delete

echo "$(date -u +%FT%TZ) backup $file ($(du -h "$file" | cut -f1))"

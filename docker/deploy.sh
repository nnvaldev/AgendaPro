#!/usr/bin/env bash
# Despliega una imagen de AgendaPro en el servidor y vuelve a la anterior si
# la nueva no queda sana. Lo ejecuta .github/workflows/deploy.yml por SSH.
#
#   echo "$TOKEN_GHCR" | ./deploy.sh ghcr.io/usuario/agendapro:abc123
#
# El token de GitHub Container Registry llega por stdin (no queda en `ps`).
# SKIP_PULL=1 usa una imagen local (para probar el script sin registry).
#
# La imagen en producción queda en .env.deployed (APP_IMAGE=…): así los
# comandos manuales usan la versión que corre de verdad, no :latest.
set -euo pipefail
cd "$(dirname "$0")"

IMAGE="${1:?Uso: deploy.sh <imagen>}"
DEPLOYED=.env.deployed
compose() { docker compose -f compose.prod.yaml --env-file .env.production --env-file "$DEPLOYED" "$@"; }

if [ ! -t 0 ]; then
    token="$(cat)"
    if [ -n "$token" ]; then
        printf '%s' "$token" | docker login ghcr.io -u "${GHCR_USER:-deploy}" --password-stdin > /dev/null
        trap 'docker logout ghcr.io > /dev/null 2>&1 || true' EXIT
    fi
fi

previous="$(sed -n 's/^APP_IMAGE=//p' "$DEPLOYED" 2> /dev/null || true)"
touch "$DEPLOYED"

echo "→ Imagen: $IMAGE"
if [ "${SKIP_PULL:-0}" != 1 ]; then
    docker pull --quiet "$IMAGE"
fi

# Las variables de la shell tienen prioridad sobre los --env-file.
export APP_IMAGE="$IMAGE"

echo "→ Base de datos y Redis"
compose up -d --wait postgres redis

# Las migraciones van antes de cambiar la app y deben ser compatibles con la
# versión anterior: si hay que volver atrás, el esquema no se revierte.
echo "→ Migraciones"
compose run --rm --no-deps app php artisan migrate --force

echo "→ App, worker y Scheduler"
if ! compose up -d --wait --wait-timeout 120 --remove-orphans app queue scheduler; then
    echo "✗ La nueva versión no quedó sana."
    compose logs --tail 50 app >&2 || true

    if [ -n "$previous" ] && [ "$previous" != "$IMAGE" ]; then
        echo "↩ Volviendo a $previous"
        APP_IMAGE="$previous" compose up -d --wait --wait-timeout 120 app queue scheduler
    fi

    exit 1
fi

echo "APP_IMAGE=$IMAGE" > "$DEPLOYED"

# Backup diario de la base de datos (idempotente: reemplaza la línea anterior).
if command -v crontab > /dev/null; then
    backup_job="15 4 * * * $PWD/backup.sh >> $PWD/backups/backup.log 2>&1"
    (crontab -l 2> /dev/null | grep -v '/backup.sh' || true; echo "$backup_job") | crontab -
fi

# Libera disco: imágenes sin usar de más de una semana (conserva la anterior).
docker image prune --all --force --filter "until=168h" > /dev/null

echo "✓ Desplegado $IMAGE"

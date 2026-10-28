# syntax=docker/dockerfile:1

# Imagen de producción de AgendaPro: FrankenPHP (PHP 8.5 + Caddy con HTTPS
# automático). Se compila sobre Debian (las dependencias nativas de Vite
# necesitan glibc) y se ejecuta sobre Alpine, que pesa menos de la mitad.
# El desarrollo local sigue usando Laravel Sail (compose.yaml).

ARG BUILD_IMAGE=dunglas/frankenphp:1-php8.5-trixie
ARG RUNTIME_IMAGE=dunglas/frankenphp:1-php8.5-alpine
ARG PHP_EXTENSIONS="pdo_pgsql redis intl pcntl bcmath zip opcache"

# ---------------------------------------------------------------------------
# Dependencias PHP sin las de desarrollo. Primero solo composer.json/lock para
# reutilizar la capa mientras no cambien las dependencias.
FROM ${BUILD_IMAGE} AS vendor
ARG PHP_EXTENSIONS

RUN install-php-extensions ${PHP_EXTENSIONS}
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && php artisan package:discover --ansi

# ---------------------------------------------------------------------------
# Frontend. Parte de la etapa vendor porque el plugin de Wayfinder ejecuta
# `php artisan wayfinder:generate` durante el build.
FROM vendor AS assets

COPY --from=node:22-trixie-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-trixie-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

RUN npm ci --no-audit --no-fund && npm run build

# ---------------------------------------------------------------------------
FROM ${RUNTIME_IMAGE} AS production
ARG PHP_EXTENSIONS

RUN install-php-extensions ${PHP_EXTENSIONS}

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    SERVER_NAME=:80

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-agendapro.ini"

WORKDIR /app

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build
COPY docker/entrypoint.sh /usr/local/bin/agendapro-entrypoint

# Usuario sin privilegios. setcap le permite abrir los puertos 80/443.
RUN adduser -D -u 1000 agendapro \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
    && chown -R agendapro:agendapro /app/storage /app/bootstrap/cache /data/caddy /config/caddy \
    && chmod +x /usr/local/bin/agendapro-entrypoint

USER agendapro

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://localhost/up || exit 1

ENTRYPOINT ["agendapro-entrypoint"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]

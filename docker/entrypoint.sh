#!/bin/sh
# Cachea configuración, rutas, vistas y eventos al arrancar: deben generarse con
# las variables de entorno reales del servidor, no en el build de la imagen.
set -e

# El .dockerignore deja fuera el contenido de storage/: se recrea vacío.
mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views

php artisan optimize --no-interaction

# Delega en el entrypoint original: sin argumentos o con opciones (--config …)
# arranca FrankenPHP; con un comando (p. ej. `php artisan queue:work`) lo ejecuta.
exec docker-php-entrypoint "$@"

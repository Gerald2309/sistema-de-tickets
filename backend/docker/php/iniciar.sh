#!/bin/sh
# Arranque de los contenedores de Laravel (app y reverb): deja la aplicación lista sin pasos manuales.
# Uso: iniciar.sh <comando>. Solo el contenedor de php-fpm prepara la base; los demás esperan a que esté
# lista (healthcheck de `app` en docker-compose.yml).
set -e

# Dependencias: el volumen `vendor` empieza vacío la primera vez.
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --no-progress
fi

# La configuración llega por el entorno (docker-compose.yml). Un .env vacío evita que Laravel avise de
# que no lo encuentra; si alguien pone valores en él, el entorno del contenedor sigue mandando.
[ -f .env ] || : > .env

# Clave de la aplicación: si no viene en el entorno, se genera una propia la primera vez y se conserva en
# storage/app.key (ignorado por git). Nunca se publica una clave en el repositorio.
if [ -z "$APP_KEY" ]; then
    if [ ! -f storage/app.key ]; then
        php -r 'echo "base64:".base64_encode(random_bytes(32));' > storage/app.key
    fi
    APP_KEY="$(cat storage/app.key)"
    export APP_KEY
fi

if [ "$1" = "php-fpm" ]; then
    chown -R www-data:www-data storage bootstrap/cache
    php artisan app:preparar
    # Señal para el healthcheck: la aplicación está lista para recibir peticiones.
    touch /tmp/lista
fi

exec "$@"

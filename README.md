# Incidentes

API REST para gestionar solicitudes e incidentes. Laravel 13, PHP 8.5, MySQL 8.4 y Docker.

## Requisitos

Docker con Docker Compose.

## Puesta en marcha

1. Copiar la configuración y completar `DB_PASSWORD` y `DB_ROOT_PASSWORD`:

   ```
   cp .env.example .env
   ```

2. Levantar los contenedores e instalar las dependencias:

   ```
   docker compose up -d --build
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   ```

La API queda en `http://localhost:8080` (cambiar el puerto con `WEB_PORT` en `.env`). Comprobación: `GET /up` responde 200.

Los comandos de `artisan` que escriben en `storage` se ejecutan como `www-data`: `docker compose exec -u www-data app php artisan ...`.

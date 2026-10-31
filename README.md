# Psicol – Mesa de ayuda

Sistema de gestión de solicitudes e incidentes: una API REST en Laravel 13 (PHP 8.5) con MySQL 8.4 y un
frontend en Vue 3 con Vuetify. Todo corre en Docker.

## Instalación

Requisitos: Docker con Docker Compose v2, y los puertos 8000 y 8080 libres.

```
docker compose up -d
```

No hace falta nada más. El primer arranque tarda unos minutos porque construye las imágenes e instala las
dependencias. Al arrancar, el contenedor `app` instala las dependencias de Composer si faltan, genera la clave
de la aplicación (`backend/storage/app.key`), ejecuta las migraciones y, si la base está vacía, la siembra.

- Aplicación: **http://localhost:8000**
- API: `http://localhost:8000/api/v1` (o directa en `http://localhost:8080/api/v1`)
- Comprobación de salud: `GET http://localhost:8080/up` responde 200

| Servicio | Qué es |
|---|---|
| `web` | Nginx con el frontend compilado. Reenvía `/api` a la API y `/app` al WebSocket. |
| `api` | Nginx delante de Laravel. |
| `app` | PHP 8.5-FPM con Laravel. |
| `reverb` | Laravel Reverb, para el tiempo real por WebSocket. |
| `db` | MySQL 8.4, con las bases de desarrollo y de pruebas. |

Para detenerlo, `docker compose down`. Para borrar también los datos, `docker compose down -v`.

## Variables de entorno

Docker Compose lee `.env.example` y, si existe, `.env`, cuyos valores reemplazan a los primeros. Por eso
funciona sin preparar nada. Los valores de `.env.example` son **solo de demostración**: para cualquier otro
uso, copie el archivo a `.env` y cambie las contraseñas y los secretos.

| Variable | Para qué sirve | Valor de demostración |
|---|---|---|
| `WEB_PORT` / `API_PORT` | Puertos de la aplicación y de la API directa | `8000` / `8080` |
| `APP_KEY` | Clave de cifrado de Laravel. Vacía: se genera sola al primer arranque | vacía |
| `APP_DEBUG` | Detalle de errores. Siempre `false` fuera de desarrollo | `false` |
| `DB_DATABASE` / `DB_DATABASE_TEST` | Base de desarrollo y base de las pruebas | `incidentes` / `incidentes_test` |
| `DB_USERNAME` / `DB_PASSWORD` / `DB_ROOT_PASSWORD` | Credenciales de MySQL | de demostración |
| `ADMIN_NOMBRE` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` | Primer administrador, creado al sembrar | ver Credenciales de prueba |
| `DEMO_PASSWORD` | Contraseña común de las cuentas de prueba | ver Credenciales de prueba |
| `SANCTUM_EXPIRATION` | Minutos que dura un token de sesión | `480` |
| `SESIONES_MAXIMAS` | Sesiones abiertas por persona; al pasarse se cierran las más antiguas | `5` |
| `LIMITE_API_POR_MINUTO` | Peticiones por minuto a la API | `300` |
| `LIMITE_LOGIN_POR_EMAIL` / `LIMITE_LOGIN_POR_IP` | Intentos de inicio de sesión por minuto | `5` / `30` |
| `AVISO_VENTANA_HORAS` / `AVISO_MINIMO_INCIDENTES` / `AVISO_MINIMO_PERSONAS` | Cuándo avisar de un posible incidente masivo | `2` / `5` / `3` |
| `EXPORTACION_MAXIMA` | Máximo de filas por exportación a Excel o CSV | `100000` |
| `TRUSTED_PROXIES` | Proxies de confianza, para conocer la IP real | redes privadas de Docker |
| `CORS_ALLOWED_ORIGINS` | Orígenes externos admitidos. Vacía: ninguno, porque el frontend comparte origen | vacía |
| `REVERB_*` | Configuración del servidor de WebSockets | de demostración |
| `VOLUMEN_INCIDENTES` | Cantidad de incidentes del seeder de volumen | `200000` |

## Migraciones

Se ejecutan solas al arrancar. Para lanzarlas a mano:

```
docker compose exec app php artisan migrate
```

Para empezar de cero, borrando y volviendo a sembrar la base de desarrollo:

```
docker compose exec app php artisan migrate:fresh --seed
```

## Seeders

`php artisan db:seed` (automático en la primera instalación) ejecuta:

| Seeder | Qué crea |
|---|---|
| `PermisosSeeder` | El catálogo fijo de 13 permisos |
| `RolesSeeder` | Los roles Administrador, Soporte N1, N2 y N3, y Solicitante, con sus permisos |
| `AdministradorSeeder` | El primer administrador, con `ADMIN_*` |
| `DatosDemoSeeder` | Fuera de producción: 5 empresas cliente, usuarios de cada perfil, 750 incidentes en todos los estados con su historial, y la auditoría de las altas |

Aparte, para medir el rendimiento sobre una base vacía:

```
docker compose exec app php artisan db:seed --class=VolumenSeeder
```

## Credenciales de prueba

Todas las cuentas usan la contraseña de demostración **`demostracion mesa de ayuda psicol`**
(`ADMIN_PASSWORD` y `DEMO_PASSWORD` de `.env.example`).

| Perfil | Correo | Qué puede hacer |
|---|---|---|
| Administrador | `admin@empresa-ejemplo.com` | Todo: incidentes, usuarios, empresas, roles y permisos, auditoría |
| Soporte N1 | `cristina.guillen@empresa-ejemplo.com` | Ve todos los incidentes, toma uno libre y lo trabaja; tiene «Mi día» |
| Soporte N2 | `diego.betancourt@empresa-ejemplo.com` | Igual que N1, para el segundo nivel |
| Soporte N3 | `valentina.ojeda@empresa-ejemplo.com` | Igual que N1, para el tercer nivel |
| Solicitante | `ursula.apodaca@empresa-ejemplo.com` | Reporta incidentes, sigue los suyos en «Mis solicitudes», conversa con soporte y confirma la solución |

El resto de usuarios de la demostración tiene contraseñas aleatorias.

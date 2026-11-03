import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Pagina} */
export const instalacion = {
  id: 'instalacion',
  titulo: 'Instalación',
  grupo: 'tecnico',
  icono: iconos.descargar,
  entradilla:
    'Solo hace falta Docker con Docker Compose v2 y los puertos 8000 y 8080 libres. Todo lo demás (base de datos, cola, tiempo real y datos de ejemplo) se levanta solo.',
  secciones: [
    {
      titulo: 'Levantarlo',
      bloques: [
        {
          tipo: 'codigo',
          codigo: `git clone https://github.com/Gerald2309/sistema-de-tickets.git
cd sistema-de-tickets
docker compose up -d`,
        },
        {
          tipo: 'filas',
          filas: [
            [
              'Primer arranque',
              'Tarda unos minutos: construye las imágenes e instala dependencias. El contenedor `app` genera la clave, migra y, si la base está vacía, la siembra.',
            ],
            ['Aplicación', '`http://localhost:8000` (en Windows conviene `http://127.0.0.1:8000`).'],
            ['API', '`http://localhost:8000/api/v1`, o directa en `http://localhost:8080/api/v1`.'],
            ['Salud', '`GET http://localhost:8080/up` responde 200.'],
            ['Detener', '`docker compose down`; para borrar también los datos, `docker compose down -v`.'],
          ],
        },
      ],
    },
    {
      titulo: 'Cuentas de prueba',
      extra: 'contraseña Psicol-Mesa-De-Ayuda-2026',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['Administrador', '`admin@empresa-ejemplo.com`: todo, incluidas automatizaciones y auditoría.'],
            [
              'Soporte N1',
              '`cristina.guillen@empresa-ejemplo.com`: ve todos los incidentes, toma uno libre y lo trabaja.',
            ],
            ['Soporte N2', '`diego.betancourt@empresa-ejemplo.com`.'],
            ['Soporte N3', '`valentina.ojeda@empresa-ejemplo.com`.'],
            ['Solicitante', '`ursula.apodaca@empresa-ejemplo.com`: reporta, sigue los suyos y confirma la solución.'],
          ],
        },
      ],
    },
    {
      titulo: 'Servicios',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['web', 'Nginx con el frontend compilado. Reenvía `/api` a la API y `/app` al WebSocket.'],
            ['api', 'Nginx delante de Laravel.'],
            ['app', 'PHP 8.5-FPM con Laravel.'],
            ['reverb', 'Laravel Reverb, para el tiempo real por WebSocket.'],
            ['redis', 'Cola de trabajos y caché.'],
            ['cola', 'Worker de la cola: avisos de tiempo real, automatizaciones y notificaciones.'],
            [
              'programador',
              'Tareas programadas: revisión de automatizaciones cada 5 minutos y limpieza de tokens vencidos.',
            ],
            ['db', 'MySQL 8.4, con las bases de desarrollo y de pruebas.'],
          ],
        },
      ],
    },
    {
      titulo: 'Correr las pruebas',
      bloques: [
        {
          tipo: 'codigo',
          codigo: `# Backend: PHPUnit sobre la base incidentes_test, nunca la de desarrollo
docker compose exec app php artisan test

# Frontend: lint, formato, estilos, tipos, pruebas y compilación
cd frontend && npm ci && npm run verificar

# Recorridos de punta a punta, con la aplicación levantada
npx playwright test`,
        },
      ],
    },
  ],
}

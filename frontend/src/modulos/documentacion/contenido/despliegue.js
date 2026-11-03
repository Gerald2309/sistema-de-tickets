import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Pagina} */
export const despliegue = {
  id: 'despliegue',
  titulo: 'Despliegue y operación',
  enMenu: 'Despliegue',
  grupo: 'tecnico',
  icono: iconos.actualizar,
  entradilla:
    'La demostración en línea corre con el mismo Docker Compose, en un servidor compartido, detrás de un túnel de Cloudflare: el servidor no abre puertos a internet.',
  secciones: [
    {
      titulo: 'Cómo está montado',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['Entrada', 'Cloudflare Tunnel con HTTPS; el contenedor `web` es lo único que recibe tráfico.'],
            [
              'Aislamiento',
              'Proyecto de Compose propio, con su red y sus volúmenes; un archivo de sobrescritura del servidor fija límites de memoria y CPU por contenedor.',
            ],
            [
              'Datos',
              'MySQL y Redis en volúmenes con nombre; Redis con `appendonly` para no perder la cola al reiniciar.',
            ],
            ['Trabajos', 'La cola corre con `--tries=3 --backoff=5 --max-time=3600`: se reinicia sola cada hora.'],
          ],
        },
      ],
    },
    {
      titulo: 'Desplegar una versión',
      bloques: [
        {
          tipo: 'codigo',
          codigo: `git pull
docker compose build
docker compose up -d
docker compose exec app php artisan migrate --force`,
        },
        {
          tipo: 'filas',
          filas: [
            ['Antes', 'Respaldo de la base con `mysqldump` comprimido.'],
            ['Después', 'Comprobar `/up`, la aplicación, la documentación y que no queden migraciones pendientes.'],
          ],
        },
      ],
    },
    {
      titulo: 'Solución de problemas',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Faltan clases de una dependencia nueva',
              'El volumen `vendor` conserva las dependencias anteriores: correr `composer install` dentro del contenedor y reiniciar `app`.',
            ],
            [
              'La API responde 502',
              'El Nginx de la API guardó la IP vieja de `app` al recrearlo: reiniciar solo `api`.',
            ],
            [
              'Los avisos no llegan',
              'Revisar que corran `cola` y `reverb`. Sin WebSocket, la interfaz igual se actualiza con su refresco de respaldo.',
            ],
            ['Cambié código y la cola no lo ve', 'El worker carga el código al arrancar: reiniciar `cola`.'],
          ],
        },
      ],
    },
  ],
}

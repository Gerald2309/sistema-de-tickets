import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Pagina} */
export const api = {
  id: 'api',
  titulo: 'Referencia de la API',
  grupo: 'tecnico',
  icono: iconos.api,
  entradilla:
    'Generada desde el código con Scramble (OpenAPI) y mostrada con Scalar: no puede quedar desactualizada. Se puede iniciar sesión con una cuenta de prueba y enviar peticiones desde aquí.',
  secciones: [
    {
      titulo: 'Convenciones',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['Base', '`/api/v1`, JSON de entrada y salida.'],
            ['Autenticación', 'Token Bearer de Sanctum, que entrega `POST /auth/login` y revoca `POST /auth/logout`.'],
            ['Autorización', 'Cada operación declara su permiso; sin permiso responde 403.'],
            ['Errores', 'Formato de problema (RFC 9457) con el detalle por campo en los 422.'],
            ['Límites', '`throttle:api` por persona o IP, y un límite propio para el inicio de sesión.'],
            [
              'Idempotencia',
              'Crear incidentes y mensajes acepta `Idempotency-Key`; un reintento devuelve lo ya creado.',
            ],
            ['Contrato', '`/api/v1/openapi.json`, para generar clientes o importarlo en otras herramientas.'],
          ],
        },
      ],
    },
    { titulo: 'Probar la API', extra: 'Scalar', bloques: [{ tipo: 'api' }] },
  ],
}

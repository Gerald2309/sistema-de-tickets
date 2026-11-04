import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Pagina} */
export const seguridad = {
  id: 'seguridad',
  titulo: 'Seguridad',
  grupo: 'tecnico',
  icono: iconos.bloqueado,
  entradilla:
    'Amenazas y controles según STRIDE, con la prueba que vigila cada uno. La autorización real la aplica siempre la API; la interfaz solo la refleja. Resumen de `docs/modelo-de-amenazas.md`.',
  secciones: [
    {
      titulo: 'Simulador de permisos',
      extra: 'misma regla que AlcanceIncidentes e IncidentePolicy',
      bloques: [
        { tipo: 'permisos' },
        {
          tipo: 'nota',
          texto:
            'Cuatro capas, en orden: autenticación (401), ruta y política (404 si no lo ve, 403 si lo ve pero no puede), permiso por campo (403) y el caso de uso (409 si otra persona lo tomó primero). Cada capa enlaza a su código.',
        },
      ],
    },
    {
      titulo: 'Roles y permisos',
      extra: 'permiso_rol de los datos de ejemplo',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Administrador',
              'Todos los permisos: `crear_incidente`, `ver_todos`, `editar_todos`, `cambiar_estado`, `reasignar`, `borrar`, `ver_historial`, `ver_dashboard`, `administrar_roles`, `administrar_usuarios`, `ver_auditoria`, `administrar_automatizaciones`.',
            ],
            [
              'Soporte N1, N2 y N3',
              '`crear_incidente`, `ver_todos`, `editar_todos`, `cambiar_estado`, `ver_historial`, `ver_dashboard`.',
            ],
            ['Solicitante', '`crear_incidente`, `ver_propios`, `editar_propios`, `ver_historial`.'],
            [
              'Denegar por defecto',
              'Toda ruta declara su autorización; la que no lo hace queda cerrada (`ExigirAutorizacionDeclarada`). El frontend aplica la misma regla en `meta.permiso`.',
            ],
            [
              'Último administrador',
              'Un bloqueo impide quitar o desactivar al último, también con operaciones simultáneas (`UltimoAdministradorConcurrenciaTest`).',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Amenazas y controles (STRIDE)',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Suplantación: probar contraseñas',
              'Límite por correo (5/min, contado sin tildes ni mayúsculas, como compara MySQL) y por IP (30/min, la que informa Cloudflare, no la que manda el cliente); mensaje genérico; hash ficticio para no revelar por el tiempo qué cuentas existen; argon2id; alerta al superar el umbral (`VigilarIniciosFallidos`, `AlertasDeSeguridadTest`).',
            ],
            [
              'Suplantación: robar un token',
              'El token solo viaja al mismo origen; CSP estricta; CORS cerrado (`cliente.js`, `CorsTest`). HTTP redirige a HTTPS y HSTS fija HTTPS en el navegador. Vence a las 8 horas (`SANCTUM_EXPIRATION=480`).',
            ],
            [
              'Manipulación: tocar lo ajeno',
              'Regla única `AlcanceIncidentes`; permiso por campo; el creador y el estado nunca se toman de la petición (`ActualizarIncidenteTest`).',
            ],
            [
              'Manipulación: inyección',
              'Consultas con parámetros; búsqueda limpia de operadores; `LIKE` escapado; celdas con fórmula salen como texto en el CSV (`BuscarIncidentesTest`, `ExportarIncidentesTest`).',
            ],
            [
              'Manipulación: archivos',
              'Tipo comprobado por el contenido, no por la extensión; sin SVG; tamaño y cantidad acotados; nombre aleatorio al guardar (`ConversacionDeIncidenteTest`).',
            ],
            [
              'Repudio',
              'Auditoría solo de inserción; historial por incidente con autor; identificador de correlación en los logs.',
            ],
            [
              'Divulgación: probar ids',
              'Lo que no se ve responde 404, igual que lo inexistente; sin token, 401 antes de buscar el recurso (`ContratoDeApiTest`).',
            ],
            [
              'Divulgación: notas internas',
              'Se filtran en la consulta y también en las imágenes adjuntas (`ConversacionDeIncidenteTest`).',
            ],
            ['Divulgación: errores', 'Errores RFC 9457; un 500 solo devuelve el identificador; `APP_DEBUG=false`.'],
            [
              'Denegación de servicio',
              'Máximo por página y por exportación; 300 peticiones por minuto por persona; tope de tamaño en Nginx y en PHP.',
            ],
            [
              'Elevación de privilegios',
              'Toda ruta declara su permiso y la que no lo hace queda cerrada (`ExigirAutorizacionDeclarada`, `ContratoDeApiTest`). Quien administra usuarios no asigna permisos de administración que no tiene; una automatización no actúa si su autor perdió el permiso.',
            ],
            [
              'Cadena de suministro',
              'Archivos de bloqueo; `composer audit` y `npm audit` en la integración continua; imágenes fijadas por digest; Dependabot.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Cabeceras y navegador',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Aplicación',
              "CSP `default-src 'self'` sin scripts de terceros, `frame-ancestors 'none'`, `X-Frame-Options: DENY` y `Permissions-Policy` restrictiva (`frontend/docker/cabeceras.conf`).",
            ],
            ['API', "CSP `default-src 'none'` y `X-Frame-Options: DENY` en cada respuesta (`CabecerasDeApi`)."],
            [
              'Visor de la API',
              'Scalar con versión fija e integridad (SRI); solo se conecta a su propio origen y solo lo puede incrustar este mismo sitio (`CabecerasDeDocumentacion`).',
            ],
            ['Tiempo real', 'Canales privados, autorizados con el mismo token contra `/api/v1/broadcasting/auth`.'],
          ],
        },
      ],
    },
    {
      titulo: 'Riesgos aceptados',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Sin segundo factor',
              'Una contraseña robada de un administrador basta para entrar. Mitigado por los límites y las alertas.',
            ],
            [
              'Alertas dentro de la aplicación',
              'Se registran en la auditoría y en el log, pero nadie recibe un aviso fuera de ella.',
            ],
            [
              'Valores de demostración',
              '`.env.example` trae valores públicos a propósito, para que `docker compose up -d` funcione sin preparar nada. En cualquier otro uso se reemplazan en `.env`.',
            ],
            [
              'Fuera del código',
              'Política de seguridad, análisis de riesgos, continuidad y seguridad física: los cubre el sistema de gestión (ISO/IEC 27001) de quien opere la aplicación.',
            ],
          ],
        },
      ],
    },
  ],
}

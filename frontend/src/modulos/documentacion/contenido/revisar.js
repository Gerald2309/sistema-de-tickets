import { iconos } from '@/compartido/iconos.js'

/** @type {import('../tipos.js').Pagina} */
export const revisar = {
  id: 'revisar',
  titulo: 'Para revisar el proyecto',
  enMenu: 'Para revisar',
  grupo: 'inicio',
  icono: iconos.exito,
  entradilla:
    'Por dónde empezar si tiene poco tiempo: qué leer en orden, qué comprobar en la demo con las cuentas de prueba y qué limitaciones conocemos. Cada ruta de archivo enlaza al código en GitHub.',
  secciones: [
    {
      titulo: 'Ruta de 10 minutos',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['1 · Visión general', 'La página Arquitectura (planos C4) y el `README.md`: qué hay y cómo se levanta.'],
            [
              '2 · Las fronteras',
              '`backend/tests/Unit/ArquitecturaTest.php` y `frontend/pruebas/unitarias/arquitectura.test.js`: los módulos dependen en un solo sentido y el dominio no conoce Laravel.',
            ],
            [
              '3 · La regla de acceso',
              '`backend/app/Incidentes/Dominio/AlcanceIncidentes.php`, con el simulador de la página Seguridad al lado.',
            ],
            [
              '4 · Un caso de uso completo',
              '`backend/app/Incidentes/Acciones/CrearIncidente.php`, siguiendo la página Recorrido de un incidente.',
            ],
            [
              '5 · Concurrencia e idempotencia',
              '`backend/app/Automatizaciones/Acciones/AplicarAutomatizaciones.php`: una regla actúa una sola vez aunque lleguen dos trabajos.',
            ],
            [
              '6 · Tiempo real',
              '`frontend/src/nucleo/plugins/tiempo-real.js` y `backend/app/Incidentes/Acciones/AvisarCambios.php`.',
            ],
            ['7 · Lo que vigila cada cambio', '`.github/workflows/integracion-continua.yml`.'],
          ],
        },
      ],
    },
    {
      titulo: 'Comprobarlo en la demo',
      extra: 'contraseña de las cuentas: Psicol-Mesa-De-Ayuda-2026',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            [
              'Lo ajeno no existe',
              'Entre como Solicitante (`ursula.apodaca@empresa-ejemplo.com`), abra uno de sus incidentes y cambie el número en la dirección por el de otro: responde «no encontrado», igual que uno inexistente.',
            ],
            [
              'Tiempo real',
              'Abra dos navegadores, uno como Soporte N1 (`cristina.guillen@empresa-ejemplo.com`) y otro como el Solicitante. Escriba en la conversación de un incidente: aparece al instante en el otro.',
            ],
            [
              'Tomar antes de trabajar',
              'Como Soporte N1, el estado de un incidente solo se puede cambiar después de tomarlo.',
            ],
            [
              'Una regla sin activarla',
              'Como Administrador (`admin@empresa-ejemplo.com`), abra una automatización y pulse «Probar»: dice a cuántos incidentes les tocaría ahora y por qué camino irían.',
            ],
            [
              'Fuerza bruta',
              'Intente iniciar sesión con una contraseña equivocada varias veces seguidas con el mismo correo: al sexto intento en un minuto, el límite lo frena.',
            ],
            [
              'La API',
              'En Referencia de la API, pida un token con `POST /auth/login` y pruebe `GET /incidentes` con cada cuenta.',
            ],
          ],
        },
      ],
    },
    {
      titulo: 'Limitaciones conocidas',
      bloques: [
        {
          tipo: 'filas',
          filas: [
            ['Sin segundo factor', 'Mitigado por los límites de intentos y las alertas de seguridad.'],
            ['Alertas dentro de la aplicación', 'Se registran en la auditoría y el log; no se envían por correo.'],
            ['Festivos', 'El horario laboral no descuenta festivos.'],
            [
              'Demo abierta',
              'Las cuentas de prueba, incluida la de administrador, usan una contraseña pública para que cualquiera pueda evaluar.',
            ],
            [
              'Visor de la API',
              'Scalar muestra su interfaz en inglés; la documentación de cada operación está en español.',
            ],
          ],
        },
      ],
    },
  ],
}

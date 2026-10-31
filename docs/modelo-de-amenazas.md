# Modelo de amenazas

Qué se protege, de quién y con qué controles, para la mesa de ayuda. Sigue el método STRIDE y remite a OWASP
Top 10:2025 (A01 a A10), OWASP API Security Top 10:2023 (API1 a API10) y los controles del Anexo A de
ISO/IEC 27001:2022. Cada control indica dónde está en el código o qué prueba lo comprueba.

## Activos

| Activo | Por qué importa |
|---|---|
| Credenciales y tokens de sesión | Quien los tenga actúa como esa persona |
| Incidentes, mensajes e imágenes adjuntas | Pueden traer datos internos de la empresa cliente |
| Notas internas de soporte | No deben llegar a quien reportó el incidente |
| Datos personales (nombre, correo, IP) | Protegidos por la ley de datos personales (en Colombia, Ley 1581 de 2012) |
| Roles y permisos | Cambiarlos da acceso a todo lo demás |
| Auditoría e historial | Son la evidencia de quién hizo qué; si se alteran, se pierde la trazabilidad |
| «Mi día» (agenda, notas y bloqueos) | Es personal: solo lo ve su dueño |

## Actores

- **Anónimo en internet:** solo alcanza el inicio de sesión; todo lo demás exige token.
- **Solicitante:** ve y conversa sobre sus propios incidentes.
- **Soporte:** ve todos, trabaja lo que tiene asignado.
- **Administrador:** gestiona usuarios, roles y empresas. Es el actor con más poder: si su cuenta se compromete, el daño es mayor.

## Superficie de ataque

El inicio de sesión, los endpoints de `/api/v1`, la subida de imágenes, la exportación a Excel y CSV, los
canales privados del WebSocket y el equipo donde corre Docker.

## Amenazas y controles (STRIDE)

| Amenaza | Ejemplo | Controles | Referencia |
|---|---|---|---|
| **Suplantación** | Probar contraseñas contra una cuenta o rociar una contraseña sobre muchas | Límite por correo y por IP; mensaje genérico; hash ficticio para no revelar por el tiempo qué cuentas existen; argon2id; token con vencimiento y máximo de sesiones; alerta de seguridad al superar el umbral (`VigilarIniciosFallidos`, `AlertasDeSeguridadTest`) | A07 · API2 · ISO 8.5 |
| **Suplantación** | Robar un token desde otro sitio | El token solo viaja al mismo origen; CSP estricta; CORS cerrado (`cliente.js`, `CorsTest`) | A05 · ISO 8.26 |
| **Manipulación** | Cambiar un incidente ajeno o un campo sin permiso | Regla única `AlcanceIncidentes`; permiso por campo; el creador y el estado nunca se toman de la petición (`ActualizarIncidenteTest`) | A01 · API1 · API3 · ISO 8.3 |
| **Manipulación** | Inyectar SQL, operadores de búsqueda o fórmulas en el CSV | Consultas con parámetros; búsqueda limpia de operadores; `LIKE` escapado; celdas con fórmula salen como texto (`BuscarIncidentesTest`, `ExportarIncidentesTest`) | A05 · ISO 8.28 |
| **Manipulación** | Subir un archivo con código disfrazado de imagen | Tipo comprobado por el contenido, no por la extensión; sin SVG; tamaño y cantidad acotados; nombre aleatorio al guardar (`ConversacionDeIncidenteTest`) | A08 · ISO 8.28 |
| **Repudio** | Negar haber cambiado un rol o un incidente | Auditoría solo de inserción; historial por incidente con autor; identificador de correlación en los logs | A09 · ISO 8.15 |
| **Divulgación** | Descubrir qué incidentes existen probando ids | Lo que no se ve responde 404, igual que lo inexistente; sin token, 401 antes de buscar el recurso (`ContratoDeApiTest`) | A01 · API1 |
| **Divulgación** | Que el solicitante lea notas internas | Las notas se filtran en la consulta y en las imágenes adjuntas (`ConversacionDeIncidenteTest`) | A01 · ISO 8.3 |
| **Divulgación** | Detalles internos en un error | Errores RFC 9457; un 500 solo devuelve el identificador; `APP_DEBUG=false` (`ContratoDeApiTest`) | A10 · A02 |
| **Denegación de servicio** | Pedir listas o exportaciones enormes, o subir archivos gigantes | Máximo por página y por exportación; límite de peticiones; tope de tamaño en Nginx y en PHP | API4 · ISO 8.6 |
| **Elevación de privilegios** | Llamar a una ruta de administración sin el permiso | Toda ruta declara su permiso y la que no lo hace queda cerrada (`ExigirAutorizacionDeclarada`, `ContratoDeApiTest`) | A01 · API5 · ISO 8.2 |
| **Elevación de privilegios** | Dejar el sistema sin administradores | Bloqueo que impide quitar o desactivar al último, también con operaciones simultáneas (`UltimoAdministradorConcurrenciaTest`) | ISO 5.18 |
| **Cadena de suministro** | Una dependencia o imagen comprometida | Archivos de bloqueo; `composer audit` y `npm audit` en la integración continua; imágenes fijadas por digest; Dependabot | A03 · ISO 8.8 |

## Riesgos aceptados y siguientes pasos

- **Sin segundo factor:** una contraseña robada de un administrador basta para entrar. Mitigado por los límites y las alertas; propuesta 8 de `propuestas-de-mejora.md`.
- **Las alertas se quedan en la aplicación:** se registran en la auditoría y en el log, pero nadie recibe un aviso fuera de ella. Propuestas 7 y 8.
- **Sin TLS en el entorno de Docker:** es un entorno de evaluación local. En producción, TLS en el proxy (propuesta 9); la API ya envía HSTS cuando llega por HTTPS.
- **Valores de demostración en `.env.example`:** son públicos a propósito, para que `docker compose up -d` funcione sin preparar nada. Para otro uso se reemplazan en `.env`.
- **Fuera del alcance del código:** la política de seguridad, el análisis de riesgos de la organización, los respaldos, la continuidad del negocio y la seguridad física. Los cubre el sistema de gestión (ISO/IEC 27001) de quien opere la aplicación.

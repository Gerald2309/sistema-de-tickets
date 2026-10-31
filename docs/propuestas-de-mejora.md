# Propuestas de mejora

El enunciado invita a proponer mejoras sobre el stack mínimo sin alterar lo solicitado. Lo que sigue **no está implementado**: son los siguientes pasos que se recomiendan, ordenados por la relación entre beneficio y costo. Cada propuesta indica qué hay hoy en el código, para que se pueda verificar.

## 1. TypeScript en el frontend

- **Hoy:** el frontend está en JavaScript, como pide la prueba, pero con tipos en JSDoc comprobados por `vue-tsc` (`npm run tipos`). Ese chequeo ya detecta los errores que detectaría TypeScript.
- **Propuesta:** pasar a `<script setup lang="ts">` y archivos `.ts`.
- **Por qué:** la sintaxis es más corta que JSDoc, los tipos de props y emits se escriben de forma nativa y los editores dan mejor ayuda.
- **Costo:** bajo, porque los tipos ya están escritos y la migración es mecánica, archivo por archivo, sin cambiar el comportamiento.

## 2. Sesión con cookie en lugar de token en el navegador

- **Hoy:** el token Bearer de Sanctum vive en `sessionStorage`, viaja solo al mismo origen y la CSP es estricta. Un XSS sigue siendo la única vía para robarlo, y la CSP lo hace difícil.
- **Propuesta:** usar el modo SPA de Sanctum, con una cookie `HttpOnly`, `Secure` y `SameSite=Lax` y protección CSRF.
- **Por qué:** el token deja de estar al alcance de JavaScript.
- **Costo:** medio. Cambian el ingreso, el cliente HTTP y la autorización del WebSocket; la API y sus permisos no cambian.

## 3. Colas para los avisos en tiempo real y las tareas pesadas

- **Hoy:** el aviso por WebSocket se emite dentro de la petición (`ShouldBroadcastNow`, `QUEUE_CONNECTION=sync`). Si Reverb no responde, la operación igual se guarda y el frontend vuelve a su refresco periódico.
- **Propuesta:** Redis como cola y Laravel Horizon para vigilarla.
- **Por qué:** la petición no espera al WebSocket, y la cola permite sumar tareas en segundo plano, como avisos por correo o exportaciones muy grandes que se descargan al terminar.
- **Costo:** bajo: un contenedor de Redis y un proceso `queue:work`.

## 4. Caché del panel y conteos

- **Hoy:** el panel calcula sus totales en cada visita. La caché de Laravel usa la base de datos.
- **Propuesta:** Redis como caché, con los totales del panel guardados unos segundos e invalidados con el mismo evento que ya avisa los cambios.
- **Extra:** un endpoint que devuelva junto el resumen de una empresa en el detalle del incidente, que hoy se arma con tres conteos.
- **Costo:** bajo.

## 5. Búsqueda tolerante a errores de escritura

- **Hoy:** la búsqueda y los casos similares usan el índice `FULLTEXT` de MySQL. Es rápido con volumen, pero no tolera errores de escritura («impresroa») ni sinónimos.
- **Propuesta:** Laravel Scout con Meilisearch.
- **Por qué:** los casos similares ganan precisión y aparecen mientras se escribe el título.
- **Costo:** medio: un servicio más y la sincronización del índice.

## 6. Áreas de soporte y acuerdos de nivel de servicio

- **Hoy:** quedaron fuera de alcance por decisión de producto; los roles y permisos configurables ya dejan el camino abierto.
- **Propuesta:**
  - áreas (Redes, Aplicaciones…) con su cola de trabajo;
  - asignación automática por categoría;
  - tiempos objetivo por prioridad, con aviso antes de vencer y escalamiento.
- **Costo:** alto: requiere definición de negocio antes que código.

## 7. Observabilidad

- **Hoy:** errores con formato RFC 9457 hacia el cliente y registro de Laravel en archivo. Cada petición lleva un identificador (`X-Request-Id`) que aparece en el registro de Nginx, en cada línea de log de Laravel y en la respuesta; un error 500 solo entrega ese identificador.
- **Propuesta:**
  - registros en JSON (`LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter`) hacia un agregador;
  - Sentry (o OpenTelemetry) en backend y frontend, con ese mismo identificador;
  - Laravel Pulse para ver consultas lentas y uso.
- **Costo:** bajo.

## 8. Seguridad adicional

- **Segundo factor** (TOTP) para quienes administran usuarios y roles.
- **CSP sin `style-src 'unsafe-inline'`:** servir el HTML con un `nonce` por petición. Hoy se permite estilo en línea porque Vuetify inyecta el tema; los scripts ya son estrictos.
- **Alertas sobre la auditoría:** la auditoría ya registra los inicios fallidos; se propone alertar cuando se repitan desde una misma IP.

## 9. Despliegue

- **Hoy:** `docker compose up -d` levanta todo con valores de demostración. Es cómodo para evaluar, pero no para producción.
- **Propuesta:**
  - imágenes de producción ya compiladas (sin montar el código);
  - HTTPS y WebSocket seguro (`wss`), con el TLS en el proxy. La API ya envía `Strict-Transport-Security` cuando la petición llega por HTTPS (con `TRUSTED_PROXIES` apuntando al proxy), así que basta con activar el certificado;
  - secretos fuera del repositorio y migraciones como paso del despliegue.
- **Integración continua:** el flujo `.github/workflows/integracion-continua.yml` se añadiría a ese camino.

## 10. Calidad de las pruebas

- **Hoy:**
  - 479 pruebas en el backend, más una suite de rendimiento aparte que mide tiempos y revisa con `EXPLAIN` que el listado use índices sobre 200 000 incidentes;
  - 128 unitarias y de integración en el frontend, con más del 80 % de cobertura;
  - 21 recorridos de punta a punta en Chromium, Firefox y WebKit, con revisión de accesibilidad automática.
- **Propuesta:**
  - pruebas de mutación (Infection en PHP, Stryker en JavaScript), que miden si las pruebas detectan errores y no solo qué líneas recorren;
  - comparación visual de capturas contra el diseño aprobado.

## 11. Paginación por cursor en el listado

- **Hoy:** el listado pagina con `LIMIT/OFFSET`, con un máximo por página. Con los índices actuales, las primeras páginas responden rápido aun con 200 000 incidentes, pero una página muy profunda obliga a MySQL a saltar todas las filas anteriores.
- **Propuesta:** paginación por cursor (keyset): «los siguientes después de este id» en lugar de «la página 400».
- **Por qué no ahora:** el cursor no permite saltar a una página concreta ni ordenar por cualquier columna sin un cursor compuesto, y la tabla ofrece las dos cosas. Se cambiaría a «cargar más» junto con el diseño de la tabla.
- **Costo:** medio: cambian el contrato del listado y su pie de tabla.

## 12. Renovación de la sesión

- **Hoy:** el token expira a las 8 horas (`SANCTUM_EXPIRATION`), hay un máximo de sesiones por persona y se revoca al salir, al cambiar la contraseña o al desactivar la cuenta. Al expirar, se vuelve a iniciar sesión.
- **Propuesta:** tokens de vida corta (15 minutos) con renovación rotativa: cada renovación invalida el token anterior, y hay un tope absoluto de duración de la sesión.
- **Por qué no ahora:** renovar sin rotación ni tope alarga la vida de un token robado; hacerlo bien conviene junto con la cookie `HttpOnly` de la propuesta 2.
- **Costo:** medio.

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

- **Hoy:** Redis como cola, con un worker (`cola`) que envía los avisos de tiempo real: la petición no espera a Reverb, y si Reverb no responde el aviso se reintenta. Un programador (`programador`) ejecuta las tareas periódicas.
- **Propuesta:** Laravel Horizon para vigilar la cola, y mover a ella las exportaciones muy grandes o los avisos por correo cuando existan.
- **Costo:** bajo: Horizon reemplaza al worker actual.

## 4. Caché del panel y conteos

- **Hoy:** el resumen y los indicadores del panel se guardan en Redis por alcance (todo, lo propio o nada): 1 y 5 minutos, y se renuevan apenas cambia un incidente. Con 200 000 incidentes, los indicadores pasaron de 9–13 s a cerca de 1 s sin caché (consultas ajustadas e índice nuevo) y a 0,1–0,4 s con caché.
- **Propuesta:** un endpoint que devuelva junto el resumen de una empresa en el detalle del incidente, que hoy se arma con tres conteos.
- **Costo:** bajo.

## 5. Búsqueda tolerante a errores de escritura

- **Hoy:** la búsqueda y los casos similares usan el índice `FULLTEXT` de MySQL. Es rápido con volumen, pero no tolera errores de escritura («impresroa») ni sinónimos.
- **Propuesta:** Laravel Scout con Meilisearch.
- **Por qué:** los casos similares ganan precisión y aparecen mientras se escribe el título.
- **Costo:** medio: un servicio más y la sincronización del índice.

## 6. Áreas de soporte y acuerdos de nivel de servicio

- **Hoy:** cada empresa tiene plazos de primera respuesta y de resolución por prioridad, en horas corridas o en horario laboral; el incidente muestra su cuenta regresiva. Las áreas de soporte quedaron fuera de alcance por decisión de producto.
- **Propuesta:**
  - estados «Esperando al cliente» y «Esperando proveedor», que pausan el plazo y lo reanudan al responder;
  - festivos en el horario laboral, y un horario propio por empresa;
  - aviso cuando falta el 20 % del tiempo y escalamiento automático a un nivel superior si se incumple;
  - cierre automático de lo resuelto tras 3 a 5 días sin respuesta;
  - fusión de incidentes duplicados, sin contar el plazo del secundario;
  - áreas (Redes, Aplicaciones…) con su cola de trabajo y asignación automática por categoría.
- **Costo:** alto: los avisos, el escalamiento y el cierre requieren tareas programadas, y los estados nuevos tocan el ciclo de vida completo.

## 7. Observabilidad

- **Hoy:** errores con formato RFC 9457 hacia el cliente y registro de Laravel en archivo. Cada petición lleva un identificador (`X-Request-Id`) que aparece en el registro de Nginx, en cada línea de log de Laravel y en la respuesta; un error 500 solo entrega ese identificador.
- **Propuesta:**
  - registros en JSON (`LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter`) hacia un agregador;
  - Sentry (o OpenTelemetry) en backend y frontend, con ese mismo identificador;
  - Laravel Pulse para ver consultas lentas y uso.
- **Costo:** bajo.

## 8. Seguridad adicional

- **Hoy:**
  - las amenazas y sus controles están en `docs/modelo-de-amenazas.md`, ordenados según OWASP;
  - los inicios fallidos por encima del umbral (por cuenta y por IP) dejan una alerta de seguridad en la auditoría y una advertencia en el log;
  - las imágenes de Docker están fijadas por su digest, y Dependabot propone las actualizaciones de dependencias e imágenes.
- **Propuesta:**
  - **segundo factor** (TOTP) para quienes administran usuarios y roles (OWASP A07, ISO 27001 control 8.5). Cambia el flujo de inicio de sesión: un paso más con su propio límite de intentos, códigos de recuperación y la forma de restablecerlo;
  - **enviar las alertas fuera de la aplicación** (correo o un canal de chat) desde la advertencia del log, con el agregador de la propuesta 7;
  - **CSP sin `style-src 'unsafe-inline'`:** servir el HTML con un `nonce` por petición. Hoy se permite estilo en línea porque Vuetify inyecta el tema; los scripts ya son estrictos.
- **Costo:** medio para el segundo factor; bajo para lo demás.

## 9. Despliegue

- **Hoy:** `docker compose up -d` levanta todo con valores de demostración. Es cómodo para evaluar, pero no para producción.
- **Propuesta:**
  - imágenes de producción ya compiladas (sin montar el código);
  - HTTPS y WebSocket seguro (`wss`), con el TLS en el proxy. La API ya envía `Strict-Transport-Security` cuando la petición llega por HTTPS (con `TRUSTED_PROXIES` apuntando al proxy), así que basta con activar el certificado;
  - secretos fuera del repositorio y migraciones como paso del despliegue.
- **Integración continua:** el flujo `.github/workflows/integracion-continua.yml` se añadiría a ese camino.

## 10. Calidad de las pruebas

- **Hoy:**
  - unas 500 pruebas en el backend, más una suite de rendimiento aparte que mide tiempos y revisa con `EXPLAIN` que el listado use índices sobre 200 000 incidentes;
  - unas 140 unitarias y de integración en el frontend, con cerca del 80 % de cobertura;
  - 22 recorridos de punta a punta en Chromium, Firefox y WebKit, con revisión de accesibilidad automática.
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

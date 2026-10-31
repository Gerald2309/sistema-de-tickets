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

- **Hoy:** errores con formato RFC 9457 hacia el cliente y registro de Laravel en archivo.
- **Propuesta:**
  - registros estructurados en JSON;
  - Sentry (o OpenTelemetry) en backend y frontend, con el mismo identificador por petición;
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
  - HTTPS y WebSocket seguro (`wss`);
  - secretos fuera del repositorio y migraciones como paso del despliegue.
- **Integración continua:** el flujo `.github/workflows/integracion-continua.yml` se añadiría a ese camino.

## 10. Calidad de las pruebas

- **Hoy:**
  - 420 pruebas en el backend;
  - 114 unitarias y de integración en el frontend, con más del 80 % de cobertura;
  - 21 recorridos de punta a punta en Chromium, Firefox y WebKit, con revisión de accesibilidad automática.
- **Propuesta:**
  - pruebas de mutación (Infection en PHP, Stryker en JavaScript), que miden si las pruebas detectan errores y no solo qué líneas recorren;
  - comparación visual de capturas contra el diseño aprobado.

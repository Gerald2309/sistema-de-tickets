# Entrada única de la aplicación (D-F09): Node compila el frontend y Nginx lo sirve, reenviando la API y
# el WebSocket al resto de contenedores. Imagen final sin Node ni código fuente.

FROM node:26-alpine@sha256:6bd9ab43c0ec0190ca7b2d193906cb8b7edf6e7f4afa43ddeccd6452710bffb5 AS compilacion
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
RUN npm run build

FROM nginx:1.27-alpine@sha256:65645c7bb6a0661892a8b03b89d0743208a18dd2f3f17a54ef4b76fb8e2f2a10
# La plantilla se completa al arrancar con las variables de entorno del contenedor (solo REVERB_APP_KEY).
COPY docker/default.conf.template /etc/nginx/templates/default.conf.template
COPY docker/cabeceras.conf /etc/nginx/snippets/cabeceras.conf
COPY --from=compilacion /app/dist /usr/share/nginx/html

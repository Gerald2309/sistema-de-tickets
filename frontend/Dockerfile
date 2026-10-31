# Entrada única de la aplicación (D-F09): Node compila el frontend y Nginx lo sirve, reenviando la API y
# el WebSocket al resto de contenedores. Imagen final sin Node ni código fuente.

FROM node:24-alpine@sha256:8a32c8cf649a49915a66e81e7a609223c708d0adabf288ee3f4077d765542f85 AS compilacion
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
RUN npm run build

FROM nginx:1.29-alpine@sha256:5616878291a2eed594aee8db4dade5878cf7edcb475e59193904b198d9b830de
# La plantilla se completa al arrancar con las variables de entorno del contenedor (solo REVERB_APP_KEY).
COPY docker/default.conf.template /etc/nginx/templates/default.conf.template
COPY docker/cabeceras.conf /etc/nginx/snippets/cabeceras.conf
COPY --from=compilacion /app/dist /usr/share/nginx/html

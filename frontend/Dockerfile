# Entrada única de la aplicación (D-F09): Node compila el frontend y Nginx lo sirve, reenviando la API y
# el WebSocket al resto de contenedores. Imagen final sin Node ni código fuente.

FROM node:24-alpine AS compilacion
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
RUN npm run build

FROM nginx:1.27-alpine
# La plantilla se completa al arrancar con las variables de entorno del contenedor (solo REVERB_APP_KEY).
COPY docker/default.conf.template /etc/nginx/templates/default.conf.template
COPY docker/cabeceras.conf /etc/nginx/snippets/cabeceras.conf
COPY --from=compilacion /app/dist /usr/share/nginx/html

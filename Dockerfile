# ============================================
# Stage 1 : Compilation des assets frontend
# ============================================
FROM node:20-alpine AS assets-builder

WORKDIR /app

# Installation des dépendances Node.js
COPY package.json package-lock.json ./
RUN npm ci

# Compilation des assets avec Vite
COPY vite.config.js ./
COPY resources/ ./resources/
RUN npm run build

# ============================================
# Stage 2 : Image de production
# ============================================
FROM serversideup/php:8.5-frankenphp-alpine

# Configuration PHP
ENV PHP_OPCACHE_ENABLE=1
ENV AUTORUN_ENABLED=true

USER root

# Installation des paquets système et de l'extension GD
RUN apk add --no-cache \
    bash \
    curl \
    libpng \
    libjpeg-turbo \
    freetype \
    && apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && apk del .build-deps

# Préparation des répertoires de l'application
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache /etc/caddy  \
    && chown -R www-data:www-data /var/www/html

# Configuration du serveur web
COPY Caddyfile /etc/caddy/Caddyfile

# Copie du code source
COPY --chown=www-data:www-data . /var/www/html

# Injection des assets compilés depuis le stage 1
COPY --from=assets-builder --chown=www-data:www-data /app/public/build /var/www/html/public/build

USER www-data
WORKDIR /var/www/html

# Installation des dépendances PHP (production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress
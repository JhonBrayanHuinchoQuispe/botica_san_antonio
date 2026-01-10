# Imagen multi-stage para Laravel en Render (Apache)
# Etapa 1: Composer para instalar dependencias PHP
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock /app/
ENV COMPOSER_MEMORY_LIMIT=-1
# Instalar dependencias ignorando requisitos de plataforma (las extensiones
# estarán presentes en la imagen final). También deshabilitamos scripts para
# evitar que fallen en esta etapa.
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-progress \
    --no-scripts \
    --ignore-platform-reqs
COPY . /app
RUN composer dump-autoload --optimize

# Etapa 2: Build opcional de assets (Vite o Mix)
FROM node:18-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json /app/
RUN npm ci --legacy-peer-deps || true
COPY . /app
# Ejecutar build si existe configuración de Vite o Mix
RUN if [ -f "vite.config.js" ]; then npm run build || true; fi \
 && if [ -f "webpack.mix.cjs" ] || [ -f "webpack.mix.js" ]; then npm run production || npm run build || true; fi

# Etapa final: PHP 8.2 con Apache
FROM php:8.2-apache

# Instalar extensiones necesarias para Laravel
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip bcmath intl exif pdo pdo_mysql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Configurar DocumentRoot en /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copiar binario de composer a la imagen final
COPY --from=vendor /usr/bin/composer /usr/bin/composer

# Copiar código y vendor desde la etapa de Composer
COPY --from=vendor /app /var/www/html

# Copiar assets compilados si existen
COPY --from=assets /app/public/dist /var/www/html/public/dist

# Permisos para storage y cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Variables útiles
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PHP_OPCACHE_validate_timestamps=0

# Copiar script de arranque
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Exponer puerto default
EXPOSE 80

# Comando de arranque
CMD ["/usr/local/bin/start.sh"]
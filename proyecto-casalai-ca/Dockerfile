# 1. Usamos tu imagen de PHP
FROM php:8.3-apache

# 2. Instalamos las herramientas del sistema necesarias
RUN apt-get update && apt-get upgrade -y && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip mysqli pdo_mysql

# 3. Instalamos Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Habilitamos los módulos de Apache necesarios
RUN a2enmod rewrite headers

# Configuración crítica de Apache para puertos dinámicos en Render
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

# --- NUEVA CORRECCIÓN: Forzar modo producción en PHP para evitar Error 426 ---
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/display_errors = On/display_errors = Off/g' $PHP_INI_DIR/php.ini \
    && sed -i 's/display_startup_errors = On/display_startup_errors = Off/g' $PHP_INI_DIR/php.ini \
    && sed -i 's/log_errors = Off/log_errors = On/g' $PHP_INI_DIR/php.ini

# 5. Copiamos los archivos de tu proyecto
COPY . /var/www/html/

# 6. Definimos el directorio de trabajo e instalamos dependencias de Composer
WORKDIR /var/www/html
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 7. Exponemos el puerto dinámico asignado por Render
EXPOSE 80

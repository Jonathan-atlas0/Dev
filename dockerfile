# =====================================================
# Dockerfile - Cafeteria (PHP + Apache + PostgreSQL)
# =====================================================

FROM php:8.2-apache

# Instala dependências e extensões necessárias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilita mod_rewrite do Apache
RUN a2enmod rewrite

# Configurações de PHP para desenvolvimento
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && echo "display_errors = On" >> "$PHP_INI_DIR/php.ini" \
    && echo "error_reporting = E_ALL" >> "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize = 32M" >> "$PHP_INI_DIR/php.ini" \
    && echo "post_max_size = 32M" >> "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit = 128M" >> "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

COPY . .

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
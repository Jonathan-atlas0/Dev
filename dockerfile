# =====================================================
# Dockerfile - Cafeteria (PHP + Apache + PostgreSQL + Redis + Composer)
# =====================================================

FROM php:8.2-apache

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    libssl-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        sockets \
        pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala extensão Redis (phpredis)
RUN pecl install redis \
    && docker-php-ext-enable redis

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Habilita mod_rewrite do Apache
RUN a2enmod rewrite

# Configurações PHP
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && echo "display_errors = On" >> "$PHP_INI_DIR/php.ini" \
    && echo "error_reporting = E_ALL" >> "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize = 32M" >> "$PHP_INI_DIR/php.ini" \
    && echo "post_max_size = 32M" >> "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit = 256M" >> "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

COPY . .

# Instala dependências PHP (Ratchet etc)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]

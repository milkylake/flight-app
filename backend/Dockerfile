FROM php:8.2-fpm-alpine
# Используйте нужную версию PHP

WORKDIR /var/www/html

# Установка зависимостей PHP и расширений
RUN apk update && apk add --no-cache \
    $PHPIZE_DEPS \
    mysql-client \
    libzip-dev \
    zlib-dev \
    icu-dev \
    && docker-php-ext-install pdo pdo_mysql zip intl \
    && docker-php-ext-enable pdo_mysql

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копируем composer файлы и устанавливаем зависимости PHP
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --no-plugins --no-scripts --optimize-autoloader

# Копируем остальной код приложения
COPY . .

# Устанавливаем права (если нужно, зависит от пользователя FPM)
# RUN chown -R www-data:www-data /var/www/html

# PHP-FPM слушает порт 9000 по умолчанию
EXPOSE 9000

# CMD ["php-fpm"] # Эта команда обычно уже задана в базовом образе
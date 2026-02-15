FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libonig-dev libxml2-dev zip curl \
    && docker-php-ext-install pdo pdo_mysql mbstring bcmath gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --no-dev --optimize-autoloader

# 建立 Laravel 需要的資料夾
RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    bootstrap/cache

# 給權限
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

FROM php:8.2-fpm

# Cài extension cần thiết
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copy toàn bộ project vào container
COPY . /var/www/html

# Laravel sẽ chạy trong thư mục này
WORKDIR /var/www/html/laravel
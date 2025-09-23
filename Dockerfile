FROM php:8.2-fpm

# Cài extension cần thiết cho MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Cài extension Redis qua PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# Copy toàn bộ project vào container
COPY . /var/www/html

# Thư mục làm việc mặc định
WORKDIR /var/www/html/laravel

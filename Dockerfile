FROM php:8.2-apache

# Cài đặt extension PHP cần thiết
RUN docker-php-ext-install pdo pdo_mysql

# Cho phép .htaccess override
RUN a2enmod rewrite

# Cấu hình document root (nếu cần)
# ENV APACHE_DOCUMENT_ROOT /var/www/html/public
# RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Copy mã nguồn vào container
COPY . /var/www/html/

# Quyền truy cập
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80
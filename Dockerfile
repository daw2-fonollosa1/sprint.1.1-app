FROM php:8.4-apache

# Serve only the public/ directory, so application code and configuration stay outside the web root.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Change Apache's default document root and install the PDO driver used by Database.php.
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && docker-php-ext-install pdo_mysql

# Copy the PHP MVC application into the image; .dockerignore excludes local secrets and Git files.
WORKDIR /var/www/html
COPY . /var/www/html

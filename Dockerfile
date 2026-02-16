FROM php:8.2-apache

# Extensions PHP MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Extension PHP MongoDB
RUN apt-get update && apt-get install -y libssl-dev pkg-config \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Active mod_rewrite
RUN a2enmod rewrite

# DocumentRoot = /var/www/html/public (dossier MVC)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Applique le DocumentRoot + AllowOverride pour .htaccess
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

EXPOSE 80
CMD ["apache2-foreground"]

# Copier le projet dans le container
COPY . /var/www/html

# Permissions uploads
RUN chown -R www-data:www-data /var/www/html/public/uploads

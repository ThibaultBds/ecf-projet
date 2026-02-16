FROM php:8.2-apache

# Extensions MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# MongoDB
RUN apt-get update && apt-get install -y libssl-dev pkg-config \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache
RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

# 🔥 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 🔥 Copier projet
COPY . /var/www/html/

# 🔥 Installer dépendances
RUN composer install --no-dev --optimize-autoloader

# 🔥 Créer uploads APRÈS le COPY
RUN mkdir -p /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/public/uploads

EXPOSE 80
CMD ["apache2-foreground"]

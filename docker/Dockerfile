FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql
RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/frontend/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/sites-available/default-ssl.conf

# 🔥 Autoriser .htaccess à être pris en compte
RUN echo "<Directory /var/www/html/frontend/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" > /etc/apache2/conf-available/project.conf \
 && a2enconf project

WORKDIR /var/www/html
COPY . /var/www/html

EXPOSE 80

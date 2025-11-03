FROM php:8.2-apache

# Active les extensions utiles (mysqli, pdo, etc.)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Active mod_rewrite (utile si tu fais des routes plus tard)
RUN a2enmod rewrite

# Définition du dossier racine principal (frontend/public)
ENV APACHE_DOCUMENT_ROOT /var/www/html/frontend/public

# Configuration d’Apache avec alias pour le backend
RUN echo "Alias /backend /var/www/html/backend/public" > /etc/apache2/conf-available/ecoride.conf \
    && echo "<Directory /var/www/html/backend/public>" >> /etc/apache2/conf-available/ecoride.conf \
    && echo "    AllowOverride All" >> /etc/apache2/conf-available/ecoride.conf \
    && echo "    Require all granted" >> /etc/apache2/conf-available/ecoride.conf \
    && echo "</Directory>" >> /etc/apache2/conf-available/ecoride.conf \
    && a2enconf ecoride

# Applique le nouveau DocumentRoot
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

EXPOSE 80
CMD ["apache2-foreground"]

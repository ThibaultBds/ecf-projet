FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN apt-get update && apt-get install -y libssl-dev pkg-config msmtp unzip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configuration msmtp (redirection mail() vers Mailhog en local)
RUN printf 'defaults\nauth off\ntls off\nlogfile /var/log/msmtp.log\n\naccount mailhog\nhost mailhog\nport 1025\nfrom noreply@ecoride.fr\n\naccount default : mailhog\n' > /etc/msmtprc \
    && chmod 644 /etc/msmtprc \
    && touch /var/log/msmtp.log \
    && chmod 666 /var/log/msmtp.log

# PHP utilise msmtp comme sendmail
RUN echo 'sendmail_path = /usr/bin/msmtp --logfile /var/log/msmtp.log -t' >> /usr/local/etc/php/php.ini

RUN a2enmod rewrite

RUN rm -f /etc/apache2/mods-enabled/mpm_event.* \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.* \
    && rm -f /etc/apache2/mods-enabled/mpm_prefork.*

RUN a2enmod mpm_prefork


ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /var/www/html/

RUN composer install --no-dev --optimize-autoloader --prefer-dist

RUN mkdir -p /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/public/uploads

EXPOSE 80

CMD ["apache2-foreground"]

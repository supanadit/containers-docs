# containers-docs — plain PHP 8 app served by Apache, running behind Traefik.
#
# TLS notes: Traefik terminates TLS and forwards plain HTTP to this container.
# Apache must NOT force HTTPS — trust Traefik's X-Forwarded-* headers instead.

FROM php:8.5-apache

RUN a2enmod rewrite deflate expires headers mime remoteip setenvif

RUN printf '%s\n' \
    'RemoteIPHeader X-Forwarded-For' \
    'RemoteIPTrustedProxy 10.0.0.0/8 172.16.0.0/12 192.168.0.0/16' \
    'SetEnvIf X-Forwarded-Proto "https" HTTPS=on' \
    > /etc/apache2/conf-available/proxy-trust.conf \
    && a2enconf proxy-trust

RUN printf '%s\n' \
    '<VirtualHost *:80>' \
    '    ServerName localhost' \
    '    DocumentRoot /var/www/html/public' \
    '    <Directory /var/www/html/public>' \
    '        AllowOverride All' \
    '        Require all granted' \
    '        FallbackResource /index.php' \
    '    </Directory>' \
    '    ErrorLog ${APACHE_LOG_DIR}/error.log' \
    '    CustomLog ${APACHE_LOG_DIR}/access.log combined' \
    '</VirtualHost>' \
    > /etc/apache2/sites-available/containers-docs.conf \
    && a2dissite 000-default \
    && a2ensite containers-docs

WORKDIR /var/www/html
COPY public/ /var/www/html/public/
COPY app/ /var/www/html/app/
COPY content/ /var/www/html/content/

EXPOSE 80

CMD ["apache2-foreground"]

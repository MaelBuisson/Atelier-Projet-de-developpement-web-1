FROM php:8.5-cli

# installeur d'extensions php (gère les dépendances système)
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

# configuration php de développement (affichage des erreurs)
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN install-php-extensions pdo_pgsql intl zip @composer

WORKDIR /var/php
EXPOSE 80

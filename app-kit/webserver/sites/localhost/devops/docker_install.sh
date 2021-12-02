#!/bin/bash

# Instalando programas
apk update && \
    apk add git wget openssh
    apk add bash ca-certificates curl php7 php7-phar php7-curl \
    php7-json php7-zlib php7-xml php7-dom php7-ctype php7-zip php7-iconv \
    php7-pdo php7-pdo_mysql php7-pdo_sqlite php7-pdo_pgsql php7-mbstring php7-session \
    php7-gd php7-mcrypt php7-openssl php7-sockets php7-posix php7-ldap php7-simplexml \
    php7-intl php7-xdebug php7-xmlreader php7-xmlwriter php7-tokenizer php7-soap \
    php7-simplexml php7-fileinfo

# Instalando composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
composer install

# Instalando phpunit
wget https://phar.phpunit.de/phpunit-9.phar -O /usr/local/bin/phpunit
chmod +x /usr/local/bin/phpunit

# Instalando o PHP Code Sniffer
curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar
curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcbf.phar

mv phpcs.phar /usr/local/bin/phpcs
mv phpcbf.phar /usr/local/bin/phpcbf
chmod +x /usr/local/bin/phpcs
chmod +x /usr/local/bin/phpcbf

# Instalação concluída
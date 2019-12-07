FROM php:7.2-apache

WORKDIR /var/www/html

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get update && \
    : '必要なモジュールをインストール' && \
    apt-get install -y curl libicu-dev mailutils unzip && \
    : 'PHPの拡張モジュールをインストール' && \
    docker-php-ext-install intl pdo pdo_mysql && \
    : 'rewriteエンジン有効化' && \
    a2enmod rewrite && \
    : 'sendmail -> mhsendmail 置き換え' && \
    curl -sSLO https://github.com/mailhog/mhsendmail/releases/download/v0.2.0/mhsendmail_linux_amd64 && \
    chmod +x mhsendmail_linux_amd64 && \
    mv mhsendmail_linux_amd64 /usr/local/bin/mhsendmail

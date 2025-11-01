FROM php:8.1-fpm

#RUN apt-get update
#RUN apt-get upgrade -y

#RUN apt-get update && apt-get install -y gnupg2
#RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
#RUN curl https://packages.microsoft.com/config/ubuntu/20.04/prod.list > /etc/apt/sources.list.d/mssql-release.list
#RUN apt-get update
#RUN ACCEPT_EULA=Y apt-get -y --no-install-recommends install msodbcsql17 unixodbc-dev
#RUN pecl install sqlsrv
#RUN pecl install pdo_sqlsrv
#RUN docker-php-ext-enable sqlsrv pdo_sqlsrv

RUN apt-get update && apt-get upgrade -y

RUN apt-get update
RUN apt-get install -y \
    curl \
    bash \
    nano \
    $PHPIZE_DEPS \
    libxml2-dev \
    libpq-dev \
    mc \
    libzip-dev \
    cron

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
#    intl \
    opcache \
    soap \
    sockets \
    pdo \
    pdo_pgsql \
    pgsql \
    exif \
    zip

RUN pecl install redis \
    # Redis is installed, enable it
    && docker-php-ext-enable redis

# Setup GD extension without alpine
RUN apt-get update && \
apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev && \
docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ && \
docker-php-ext-install gd

# For debugger PHPStorm
RUN pecl install xdebug && docker-php-ext-enable xdebug \
&& echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
&& echo "xdebug.remote_host = host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
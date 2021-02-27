FROM php:7.4-cli

RUN \
    # Update sources
    apt-get update -y && \
    apt-get upgrade -y && \
    \
    # Install GIT
    apt-get install -y git && \
    \
    # Install PHP extensions dependiencies
    apt-get install -y libzip-dev zip && \
    \
    # Install PHP "zip" extension
    pecl install zip && \
    docker-php-ext-enable zip && \
    \
    # Install Composer
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    mkdir /.composer && chown -R ${DOCKER_USER}:${DOCKER_USER} /.composer && \
    \
    # Remove APT caches
    rm -rf /var/lib/apt/lists/*

WORKDIR /app

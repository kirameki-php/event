FROM php:8-cli-alpine

ENV PATH="${PATH}:/app/vendor/bin"
ENV TERM xterm-256color
ENV TZ Asia/Tokyo

COPY docker/files /
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

RUN set -e \
    && apk add --no-cache bash coreutils git \
    && apk add --no-cache --virtual=.build-deps autoconf g++ make \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del .build-deps \
    && docker-php-source delete \
    && mkdir -p /app

WORKDIR /app

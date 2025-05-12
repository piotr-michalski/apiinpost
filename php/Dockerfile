FROM php:8.4.3
RUN groupadd -g 1000 app && useradd -u 1000 -g app -m app
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=2.8.5
RUN mkdir -p /var/www/html/app
WORKDIR /var/www/html/app
RUN chown -R app:app /var/www/html/app

COPY ./src /var/www/html/app
RUN composer install --no-interaction --no-progress

USER app

CMD ["php", "create_shipment.php"]

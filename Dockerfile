FROM php:8.1-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN echo "refresh db config"
COPY . /var/www/html/
FROM mysql:5.7
COPY config/init_dbs.sql /docker-entrypoint-initdb.d/init_dbs.sql

EXPOSE 3306

FROM php:7.2.8-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apt-get update && apt-get install -y libpq-dev
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install pdo pdo_pgsql

EXPOSE 80
EXPOSE 443

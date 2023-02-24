FROM debian:bullseye-slim AS builder

RUN apt-get update \
    && apt-get -y install libjs-bootstrap make node-less \
    && rm -rf /var/lib/apt/lists/*

ADD . /src/pizza
RUN cd /src/pizza \
    && cp docker/style.less website/css \
    && make

FROM debian:bullseye-slim

RUN apt-get update \
    && apt-get -y install ssmtp apache2 locales \
        libapache2-mod-php php-mysql \
        libjs-jquery libjs-jquery-ui libjs-underscore libjs-bootstrap libjs-moment libjs-eonasdan-bootstrap-datetimepicker \
    && rm -rf /var/lib/apt/lists/*

ADD docker/ssmtp.conf /etc/ssmtp/

ADD docker/pizza.conf /etc/apache2/sites-available/
ADD cron/pizza /etc/cron.d/
ADD website /var/www/pizza
ADD docker/config.php /var/www/pizza/
COPY --from=builder /src/pizza/website/css/style.css /var/www/pizza/css/

RUN a2enmod rewrite \
    && a2dissite 000-default \
    && a2ensite pizza

RUN sed -i '/de_DE.UTF-8/s/^# //g' /etc/locale.gen \
    && locale-gen

EXPOSE 80
CMD ["apache2ctl", "-D", "FOREGROUND"]

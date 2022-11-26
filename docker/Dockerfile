FROM debian:bullseye-slim

RUN apt-get update \
    && apt-get -y install ssmtp apache2 \ 
        libapache2-mod-php php-mysql \
        libjs-jquery libjs-jquery-ui libjs-underscore libjs-bootstrap libjs-moment libjs-eonasdan-bootstrap-datetimepicker \
    && rm -rf /var/lib/apt/lists/*

ADD docker/ssmtp.conf /etc/ssmtp/

ADD docker/pizza.conf /etc/apache2/sites-available/
ADD cron/pizza /etc/cron.d/
ADD website /var/www/pizza
ADD docker/config.php /var/www/pizza/
RUN a2enmod rewrite \
    && a2dissite 000-default \
    && a2ensite pizza

EXPOSE 80
CMD ["apache2ctl", "-D", "FOREGROUND"]

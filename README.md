# Installation
Requires Apache, PHP 7, MariaDB (or MySQL). On Debian:
```sh
$ apt-get install apache2 libapache2-mod-php mariadb-server php-mysql
```

## Database Setup
Create the database using the script in `db`:
```sh
$ mariadb < createdb.sql
```

## Web Page Setup
Install the directory website in a directory where it is served by Apache, e.g. `/var/www/pizza`.
Then create a config.php by modifying `config.php.template`:
```sh
$ cp config.php.template config.php
$ nano config.php
Adjust the name, base URL, and logo of the site.
```

## Cron
To send emails when a due time of an order has come, the script `/website/script/maintenance.php`
has to be run at regular intervals. For this, a cron script similar like the one in `cron`
can be used. Copy the file to `/etc/cron.d` and adjust the path to maintance.php (if not using
`/var/www/pizza` as the install location).

## Database Update
When updating to a new version, a database update might be required. For this, run
```sh
$ mariadb < updatedb.sql
```
in the `db` directory.

## Docker

Instead of installing on a Linux server, a Docker image can be built and run via docker-compose.

The files `docker/config.php` and `docker/ssmtp.conf` have to be adjusted beforehand, the corresponding templates `config.php.template` and `docker/ssmtp.conf.template` can be used as a starting point. 

The script `setup-docker.sh` can be used to create the config files, open an editor and start `docker-compose`. The port on which to listen is 8088 per default, and can be changed in `docker/docker-compose.yml`.
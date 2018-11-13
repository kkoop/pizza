# Installation
Requires Apache, PHP 7, MariaDB (or MySQL). On Debian 9:
```sh
$ apt-get install apache2 libapache2-mod-php7.0 mariadb-server php7.0-mysql
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

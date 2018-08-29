# Installation
Requires Apache, PHP 7, MariaDB (or MySQL). On Debian 9:
$ apt-get install apache2 libapache2-mod-php7.0 mariadb-server php7.0-mysql

## Database Setup
Create the database using the script in /db:
$ mariadb < createdb.sql

## Web Page Setup
Install the directory website in a directory where it is served by Apache, e.g. /var/www/pizza.
Then create a config.php by modifying config.php.template:
$ cp config.php.template config.php
$ nano config.php
Adjust the name, base URL, and logo of the site.

## Database Update
When updating to a new version, a database update might be required. For this, run
$ mariadb < updatedb.sql
in the /db directory.

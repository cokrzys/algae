# Setup algae

## Setup PHP for algae
```shell
# save a copy of the original PHP configuration file
sudo cp /etc/php/8.5/apache2/php.ini /etc/php/8.5/apache2/php.ini.original

# edit PHP configuration making changes per below
sudo vi /etc/php/8.5/apache2/php.ini

# restart Apache
sudo systemctl restart apache2
```

Changes to PHP configuration file
```shell
# add algae to the PHP path
include_path = ".:/usr/share/php:/opt/algae-main/src/php"

# better to see errors
display_errors = On
```

## Setup Python for algae

## Setup Web Pages
```shell
# create a symbolic link to view algae web pages
sudo ln -s /opt/algae-main/web /var/www/html/algae
```

## Setup the algae admin Database
```shell
# create algae database, you will have to enter the postgres database password
sudo -u postgres createdb algae

# setup the algae data model in the new database
psql algae postgres -f /opt/algae-main/database/setup_admin_database.sql

# check reading some data
psql algae postgres -c "SELECT name FROM ref.record_status"
```

```console
   name   
----------
 Active
 InActive
(2 rows)
```

## Setup the Admin Database Password




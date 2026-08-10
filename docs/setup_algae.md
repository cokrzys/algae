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
include_path = ".:/usr/share/php:/opt/algae/algae-main/src/php"
```

## Setup Web Pages
```shell
# create a symbolic link to view algae web pages
sudo ln -s /opt/algae/algae-main/web /var/www/html/algae
```

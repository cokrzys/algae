# Setup algae

```shell
# create a symbolic link to view algae web pages
sudo ln -s /opt/algae20260807/algae-main/web /var/www/html/algae

# create a symbolic link to access the algae PHP library code
sudo ln -s /opt/algae20260807/algae-main/src/php /var/www/html/algae/lib
```

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
```console
include_path = ".:/usr/share/php:/var/www/html/algae/lib"
```

# Setup algae

```shell
sudo ln -s /opt/algae20260807/algae-main/web /var/www/html/algae

sudo ln -s /opt/algae20260807/algae-main/src/php /var/www/html/algae/lib
```

```shell
sudo cp /etc/php/8.5/apache2/php.ini /etc/php/8.5/apache2/php.ini.original

sudo vi /etc/php/8.5/apache2/php.ini

sudo systemctl restart apache2
```

```console
include_path = ".:/usr/share/php:/var/www/html/algae/lib"
```

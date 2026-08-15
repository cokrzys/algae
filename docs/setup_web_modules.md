# algae Web Modules

## jQuery and jQuery UI
Download directly into a jquery folder off the web root.
```shell
# change to web root
cd /var/www/html

# make directory for jQuery
sudo mkdir jquery
cd jquery

# jQuery
sudo wget https://code.jquery.com/jquery-3.5.1.min.js

# jQuery UI
sudo wget https://jqueryui.com/resources/download/jquery-ui-1.12.1.zip
sudo unzip jquery-ui-1.12.1.zip
sudo rm jquery-ui-1.12.1.zip

# jQuery UI themes
sudo wget https://jqueryui.com/resources/download/jquery-ui-themes-1.12.1.zip
sudo unzip jquery-ui-themes-1.12.1.zip
sudo rm jquery-ui-themes-1.12.1.zip
```

## chosen
Chosen is a jQuery extension that allows you to filter items from a selection list on a webpage.

```shell
# goto web folder for jQuery
cd /var/www/html/jquery

# make directory for chosen
mkdir chosen
cd chosen

# download chosen
wget https://github.com/harvesthq/chosen/releases/download/v1.8.7/chosen_v1.8.7.zip

# unzip
unzip chosen_v1.8.7.zip
rm unzip chosen_v1.8.7.zip
```

## tablesorter
The primary interactive table control used throughout algae.

```shell
cd /var/www/html/

sudo mkdir tablesorter
cd tablesorter

sudo wget https://github.com/Mottie/tablesorter/archive/master.zip
sudo unzip master.zip
sudo rm master.zip
```

## jscolor
A simple color picker.
cd /var/www/html/

sudo mkdir jscolor
cd jscolor

sudo wget https://jscolor.com/release/latest.zip
sudo unzip latest.zip
sudo rm latest.zip


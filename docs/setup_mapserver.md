
# Setup MapServer

```shell
# install
sudo apt-get install cgi-mapserver
sudo apt-get install mapserver-bin

# test
/usr/lib/cgi-bin/mapserv -v
```

```console
MapServer version 8.6.0 PROJ version 9.7 GDAL version 3.12 OUTPUT=PNG OUTPUT=JPEG OUTPUT=KML SUPPORTS=PROJ SUPPORTS=AGG
SUPPORTS=FREETYPE SUPPORTS=CAIRO SUPPORTS=SVG_SYMBOLS SUPPORTS=RSVG SUPPORTS=ICONV SUPPORTS=FRIBIDI SUPPORTS=WMS_SERVER
SUPPORTS=WMS_CLIENT SUPPORTS=WFS_SERVER SUPPORTS=WFS_CLIENT SUPPORTS=WCS_SERVER SUPPORTS=SOS_SERVER SUPPORTS=OGCAPI_SERV
ER SUPPORTS=FASTCGI SUPPORTS=THREADS SUPPORTS=GEOS SUPPORTS=PBF INPUT=JPEG INPUT=POSTGIS INPUT=OGR INPUT=GDAL INPUT=SHAP
EFILE INPUT=FLATGEOBUF
```

## Apache Setup for MapServer
```shell
sudo a2enmod cgi
sudo service apache2 restart
```

```console
https://rtspatial.com/cgi-bin/mapserv
```

## MapServer Configuration File


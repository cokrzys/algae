# algae Backend Database Setup

## Install PostgreSQL

Example installing PostgreSQL 18.4 on Ubuntu 26.04 LTS.
```shell
sudo apt-get install postgresql postgresql-contrib

# start PSQL shell
sudo -u postgres psql postgres
```

```sql
-- change password at the PSQL prompt
\password postgres

-- check the version
SELECT version();
```

```console
PostgreSQL 18.4 (Ubuntu 18.4-0ubuntu0.26.04.1) on x86_64-pc-linux-gnu, compiled by gcc (Ubuntu 15.2.0-16ubuntu1) 15.2.0, 64-bit
```

## Install PostGIS

```shell
sudo apt install postgis
```

## Testing

```shell
# create a test database
sudo -u postgres createdb test

# open database
sudo -u postgres psql test
```

```sql
-- install PostGIS extensions
CREATE EXTENSION postgis;
CREATE EXTENSION postgis_topology;
SELECT postgis_version();
```

```console
            postgis_version            
---------------------------------------
 3.6 USE_GEOS=1 USE_PROJ=1 USE_STATS=1
```

```shell
# delete test database
sudo -u postgres dropdb test
```
## Setup Password Authentication

```shell
sudo vi /etc/postgresql/18/main/pg_hba.conf

sudo systemctl restart postgresql
```
In the lines below ```peer``` has been changed to ```md5```.
```console
# Database administrative login by UNIX sockets
local   all         postgres                          md5

# TYPE  DATABASE    USER        CIDR-ADDRESS          METHOD

# "local" is for Unix domain socket connections only
local   all         all                               md5
```


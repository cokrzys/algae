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

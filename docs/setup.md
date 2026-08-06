
sudo apt-get install postgresql postgresql-contrib

# change the password for the postgres user, note that the postgres user is automatically created with the installation.
sudo -u postgres psql postgres

\password postgres

-- Check the version:
SELECT version();

PostgreSQL 18.4 (Ubuntu 18.4-0ubuntu0.26.04.1) on x86_64-pc-linux-gnu, compiled by gcc (Ubuntu 15.2.0-16ubuntu1) 15.2.0, 64-bit

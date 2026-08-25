# Enable Security
Security requiring user accounts and logins is off by default when algae is installed.  To enable it first set the algae root admin password then turn on security by editing the local config file.

## Updating the algae User Password
This is a manual process using a temporary webpage to create a password hash then updating the database.

Create a .php file with the following in ```/var/www/html/algae```.
```php
<?php
echo password_hash('new_password', PASSWORD_DEFAULT);
?>
```

Browse to the webpage that was just created and copy the password hash that's displayed.

```sql
UPDATE core.user SET password = 'hash_from_webpage' WHERE username = 'algae';
```


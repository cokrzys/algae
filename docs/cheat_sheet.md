## algae Framework Development Cheat Sheet

### User
Get rowid of application user.
```php
algaeTblCoreAppUser::getAppUserRowidForLoggedInUser();
```
Save a user parameter.
```php
algaeTblCoreUserParameter::save_parameter('queryToolSchemaAndTable', 'sp.project');
```
Get a user parameter.
```php
$table = algaeTblCoreUserParameter::get_parameter('queryToolSchemaAndTable');
```

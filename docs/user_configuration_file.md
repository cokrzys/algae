# algae User Configuration File

User configuration settings override defaults set in code and the framework/application configuration files, and are not changed by framework/application updates.

- Stored by default in ```/opt/rtspatial/config```
- Named the same as the framework/application
  - For example ```algae.ini```
- ini format
- Keys are case sensitive and match a code file member variable
  - PHP | [alageConfig.php](https://github.com/cokrzys/algae/blob/main/src/php/algaeConfig.php)
  - Python | alageConfig.py
- Override default location with path stored in environmental variable ```RTSPATIAL_LOCAL_CONFIG_PATH```

## Example File
```ini
; required
database_password = something

; harder to find
database_port = 53625
```

# algae User Configuration File

- Stored by default in ```/opt/rtspatial/config```
- Named the same as the framework/application
  - For example ```algae.ini```
- ini file format
- Keys are case sensitive and match a code file member variable
  - PHP alageConfig.php
  - Python alageConfig.py
- Override default location with path stored in environmental variable ```RTSPATIAL_LOCAL_CONFIG_PATH```

## Example File
```ini
; required
database_password = something

; harder to find
database_port = 53625
```

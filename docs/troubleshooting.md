# algae Troubleshooting

Check the Apache error log for lower level errors that don't show up on a webpage.
```shell
tail /var/log/apache2/error.log
```

[Enable Security](setup_security.md), algae has not been thoroughly tested with security off, and is generally not designed to work without a logged-in user.

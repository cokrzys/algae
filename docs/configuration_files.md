# algae Configuration Files

algae configuration parameters are controlled by three levels of settings with each level overriding those above it.  Equally so an application "derived" from the algae framework overrides the base configuration settings in the same order.

1. Default settings are hard coded into the PHP and Python source files.

2. A framework/application configuration file overrides the default settings.  These files should not be changed by a user, and are typically used to ensure that a setting is the same for PHP and Python.  These files can change with system updates.

3. [Local Configuration Files](user_configuration_file.md) override everything else and are typically used for passwords and other settings that are installation specific.  algae and application updates will never change these files.

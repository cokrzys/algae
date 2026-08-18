# algae Security Model

Internal database hosted security model for web access, user rights, and authentication across applications.

- User accounts, roles, accessible objects (i.e. applications), and rights to individual objects are stored in the algae admin database.

- When a user logs into an algae framework application they are authenticated via the admin database.

- When a user opens an algae based webpage or other object the rights table from the admin database is checked to see if they have access to the object.

- Individual application databases contain a user table (usernames only) that can be used for more detailed application level access.

- Usernames are a unique natural key that cannot be changed, and are synchronized between the admin and application databases.


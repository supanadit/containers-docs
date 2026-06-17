---
sidebar_position: 10
---

# Stateless Files

In stateless mode (for serverless deployments), you may need to include PHP files that are not part of the built container image. The `STATELESS_FILE_*` environment variables allow you to specify which files to copy from your persistent volume into the WordPress `wp-content` directory at startup.

:::info
This feature only works when `IS_STATELESS=true`. See [Serverless](/docs/wordpress/serverless) for more details on stateless mode.
:::

## Usage

Files are copied from `/opt/containers/data/stateless/` to `/var/www/html/wp-content/` at container startup.

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  restart: always
  environment:
    IS_STATELESS: "true"
    # Specify which files to include
    STATELESS_FILE_OBJECT_CACHE: "object-cache.php"
    STATELESS_FILE_ADVANCED_CACHE: "advanced-cache.php"
  volumes:
    - ./.data/wordpress:/opt/containers/data
```

Given the example above, the container will:

1. Check if `/opt/containers/data/stateless/object-cache.php` exists
2. If found, copy it to `/var/www/html/wp-content/object-cache.php`
3. Repeat for each `STATELESS_FILE_*` variable defined

## Environment Variable Format

```
STATELESS_FILE_<NAME>=<filename>
```

- `<NAME>`: An identifier (uppercase, no spaces)
- `<filename>`: The filename to copy from `/opt/containers/data/stateless/`

## Common Use Cases

### Object Cache (Redis/Memcached)

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  restart: always
  environment:
    IS_STATELESS: "true"
    WORDPRESS_DB_HOST: mariadb:3306
    WORDPRESS_DB_USER: root
    WORDPRESS_DB_PASSWORD: secret
    WORDPRESS_DB_NAME: wordpress

    # Include Redis object cache
    STATELESS_FILE_OBJECT_CACHE: "object-cache.php"
  volumes:
    - ./.data/wordpress:/opt/containers/data
```

Your volume structure would be:
```
./data/wordpress/
└── stateless/
    └── object-cache.php
```

### Advanced Caching Plugins

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  environment:
    IS_STATELESS: "true"
    STATELESS_FILE_OBJECT_CACHE: "object-cache.php"
    STATELESS_FILE_ADVANCED_CACHE: "advanced-cache.php"
  volumes:
    - ./.data/wordpress:/opt/containers/data
```

## File Not Found Handling

If a specified file does not exist in `/opt/containers/data/stateless/`, the startup script logs a warning and continues without error:

```
[INFO] Stateless file not found, skipping: object-cache.php
```

This allows you to define multiple files without breaking deployment if some files are optional.

## Complete Serverless Example

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  restart: always
  ports:
    - "80:80"
  environment:
    IS_STATELESS: "true"

    WORDPRESS_DB_HOST: mariadb:3306
    WORDPRESS_DB_USER: root
    WORDPRESS_DB_PASSWORD: secret
    WORDPRESS_DB_NAME: wordpress
    WORDPRESS_FS_METHOD: "direct"

    # Stateless files from persistent volume
    STATELESS_FILE_OBJECT_CACHE: "object-cache.php"

    # Security
    IS_PROTECT_XMLRPC: "true"
    IS_PROTECT_WPCONFIG: "true"
  volumes:
    - ./.data/wordpress:/opt/containers/data
```

## Requirements

- `IS_STATELESS` must be set to `"true"`
- Files must exist in `/opt/containers/data/stateless/` directory
- Files must be valid PHP files with proper ownership (www-data:www-data)

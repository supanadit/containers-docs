---
sidebar_position: 7
---

# Security Configuration

The WordPress container provides optional security features that can be enabled via environment variables. These are not enabled by default but are recommended for production deployments.

## Protect xmlrpc.php

The `xmlrpc.php` file is a popular attack vector. You can block all access to it:

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  environment:
    # ... other environment variables
    IS_PROTECT_XMLRPC: "true"
```

This adds the following to `.htaccess`:

```apache
<Files xmlrpc.php>
    order deny,allow
    deny from all
</Files>
```

:::warning
If you use the WordPress mobile app or Jetpack, blocking xmlrpc.php may cause issues. Some plugins and services rely on xmlrpc.php for API access.
:::

## Protect wp-config.php

The `wp-config.php` file contains sensitive information including database credentials. You can block direct access:

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  environment:
    # ... other environment variables
    IS_PROTECT_WPCONFIG: "true"
```

This adds the following to `.htaccess`:

```apache
<Files wp-config.php>
    Require all denied
</Files>
```

## Complete Security Example

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  restart: always
  environment:
    WORDPRESS_DB_HOST: mariadb:3306
    WORDPRESS_DB_USER: root
    WORDPRESS_DB_PASSWORD: secret
    WORDPRESS_DB_NAME: wordpress
    WORDPRESS_FS_METHOD: "direct"

    # Security features
    IS_PROTECT_XMLRPC: "true"
    IS_PROTECT_WPCONFIG: "true"
  volumes:
    - ./.data/wordpress:/opt/containers/data
```

## Security Recommendations

1. **Always use `IS_PROTECT_WPCONFIG`** - wp-config.php contains sensitive credentials
2. **Use `IS_PROTECT_XMLRPC`** if you don't need:
   - WordPress mobile app connectivity
   - Jetpack plugin
   - Trackback/pingback functionality
3. **Additional recommendations**:
   - Use HTTPS (set `IS_HTTPS: "true"`) behind a reverse proxy with SSL termination
   - Keep WordPress, themes, and plugins updated
   - Use strong database passwords
   - Implement regular backups

---
sidebar_position: 5
---

# PHP Configuration

You can customize PHP settings using environment variables in your Docker Compose configuration. This includes memory limits, upload constraints, execution timeouts, and OPcache tuning.

:::warning Minimum Memory Requirement
WordPress requires a minimum of **64MB PHP memory** to function. Settings below 64M (such as 32M) will cause **HTTP 500 errors**. If you experience "This page isn’t working" errors, increase `PHP_MEMORY_LIMIT` to at least 64M.
:::

## PHP Memory & Upload Limits

| Environment Variable | Description | Default |
|----------------------|-------------|---------|
| `PHP_MEMORY_LIMIT` | PHP memory limit per script | PHP default (128M) |
| `PHP_UPLOAD_MAX_FILESIZE` | Maximum upload file size | PHP default |
| `PHP_POST_MAX_SIZE` | Maximum POST data size | PHP default |

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  environment:
    PHP_MEMORY_LIMIT: 128M
    PHP_UPLOAD_MAX_FILESIZE: 64M
    PHP_POST_MAX_SIZE: 64M
```

## PHP Execution Timeouts

| Environment Variable | Description | Default |
|----------------------|-------------|---------|
| `PHP_MAX_EXECUTION_TIME` | Maximum script execution time (seconds) | 30 |
| `PHP_MAX_INPUT_TIME` | Maximum time for parsing request data (seconds) | 60 |

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  environment:
    PHP_MAX_EXECUTION_TIME: 30
    PHP_MAX_INPUT_TIME: 60
```

## OPcache Configuration

OPcache improves performance by caching precompiled PHP bytecode. You can tune these settings for your memory constraints.

| Environment Variable | Description | Default |
|----------------------|-------------|---------|
| `PHP_OPCACHE_ENABLE` | Enable/disable OPcache (`true`/`false`) | `true` |
| `PHP_OPCACHE_MEMORY` | OPcache memory consumption (MB) | 128 |
| `PHP_OPCACHE_MAX_ACCELERATED_FILES` | Maximum number of cached scripts | ~2000 |

### Recommended Settings

**For 256MB RAM target:**
```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  environment:
    PHP_OPCACHE_MEMORY: 32
    PHP_OPCACHE_MAX_ACCELERATED_FILES: 200
```

**For 128MB RAM target (OPcache disabled, minimum viable):**
```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  environment:
    PHP_OPCACHE_ENABLE: "false"
    PHP_MEMORY_LIMIT: 64M  # Minimum for WordPress to function
```

:::warning
Disabling OPcache will impact performance as PHP scripts will be re-compiled on every request. Only use this for extremely memory-constrained environments. Settings below 64M will cause HTTP 500 errors.
:::

## Complete Example

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

    # PHP Memory & Upload
    PHP_MEMORY_LIMIT: 128M
    PHP_UPLOAD_MAX_FILESIZE: 64M
    PHP_POST_MAX_SIZE: 64M

    # PHP Execution
    PHP_MAX_EXECUTION_TIME: 30
    PHP_MAX_INPUT_TIME: 60

    # OPcache
    PHP_OPCACHE_MEMORY: 64
    PHP_OPCACHE_MAX_ACCELERATED_FILES: 500
  volumes:
    - ./.data/wordpress:/content
```

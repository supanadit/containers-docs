---
sidebar_position: 6
---

# Low Memory Configuration

The WordPress container supports running in memory-constrained environments such as small VPS instances or containers with limited RAM. This guide covers the settings needed to optimize for low-memory deployments.

:::info
For Apache MPM-specific tuning, see [Apache MPM Configuration](./apache_mpm.md).
:::

## Low Memory Mode

The `APACHE_LOW_MEMORY_MODE` environment variable enables automatic tuning of Apache MPM settings for constrained RAM environments.

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  environment:
    APACHE_LOW_MEMORY_MODE: "true"
```

When enabled, the following settings are automatically applied based on your `APACHE_MPM` selection:

### Event MPM (Default, Most Memory-Efficient)

| Setting | Low Memory Default | Description |
|---------|-------------------|-------------|
| `StartServers` | 1 | Initial server processes |
| `MinSpareThreads` | 10 | Minimum idle threads |
| `MaxSpareThreads` | 25 | Maximum idle threads |
| `ThreadsPerChild` | 10 | Threads per server process |
| `MaxRequestWorkers` | 50 | Maximum concurrent requests |
| `MaxConnectionsPerChild` | 100 | Connections before process restart |

### Worker MPM

| Setting | Low Memory Default | Description |
|---------|-------------------|-------------|
| `StartServers` | 1 | Initial server processes |
| `MinSpareThreads` | 10 | Minimum idle threads |
| `MaxSpareThreads` | 25 | Maximum idle threads |
| `ThreadsPerChild` | 10 | Threads per server process |
| `MaxRequestWorkers` | 50 | Maximum concurrent requests |
| `MaxConnectionsPerChild` | 100 | Connections before process restart |

### Prefork MPM (Thread-Unsafe PHP, Less Efficient)

| Setting | Low Memory Default | Description |
|---------|-------------------|-------------|
| `StartServers` | 2 | Initial server processes |
| `MinSpareServers` | 1 | Minimum idle servers |
| `MaxSpareServers` | 3 | Maximum idle servers |
| `MaxRequestWorkers` | 10 | Maximum concurrent requests |
| `MaxConnectionsPerChild` | 100 | Connections before process restart |

:::warning
Prefork MPM is less memory-efficient than Event/Worker because each Apache process loads its own PHP interpreter. Use Event or Worker MPM for better memory efficiency.
:::

## Memory Optimization Checklist

For deployments under 256MB RAM:

1. **Use Event MPM** (default, already optimal)
2. **Enable Low Memory Mode**
3. **Reduce OPcache memory** or disable if needed
4. **Set explicit PHP memory limit**

## Example Configurations

### 256MB RAM Target

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

    # Use Event MPM (default, most memory-efficient)
    APACHE_MPM: event

    # Enable low memory mode
    APACHE_LOW_MEMORY_MODE: "true"

    # PHP settings
    PHP_MEMORY_LIMIT: 64M
    PHP_OPCACHE_MEMORY: 32
    PHP_OPCACHE_MAX_ACCELERATED_FILES: 200
  volumes:
    - ./.data/wordpress:/content
```

### 128MB RAM Target

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

    # Use Event MPM
    APACHE_MPM: event

    # Enable low memory mode
    APACHE_LOW_MEMORY_MODE: "true"

    # Disable OPcache for minimal memory
    PHP_OPCACHE_ENABLE: "false"
    PHP_MEMORY_LIMIT: 32M
  volumes:
    - ./.data/wordpress:/content
```

## Overriding Individual Settings

You can override specific MPM settings while still using `APACHE_LOW_MEMORY_MODE` for the others:

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  environment:
    APACHE_MPM: event
    APACHE_LOW_MEMORY_MODE: "true"
    # Override specific setting
    APACHE_MPM_EVENT_MAX_REQUEST_WORKERS: 30
```

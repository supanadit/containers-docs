---
sidebar_position: 8
---

# Apache Status Configuration

The Apache server status endpoint provides real-time information about server activity and performance. This is useful for monitoring and debugging.

## Enable Server Status

Set `APACHE_STATUS` to `true` to enable the `/server-status` endpoint:

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  environment:
    APACHE_STATUS: "true"
```

Access the status page at `http://localhost/server-status` (or `https://yourdomain.com/server-status` if using public access).

## Public Access

By default, server status is restricted to local access only (`Require local`). To make it publicly accessible:

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.9-r3
  environment:
    APACHE_STATUS: "true"
    APACHE_STATUS_PUBLIC: "true"
```

:::warning
The server status page exposes server configuration and activity information. Only enable public access in trusted environments or behind proper authentication at the proxy level.
:::

## Complete Example with Health Check

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

    # Enable server status (required for health check and exporter)
    APACHE_STATUS: "true"
    # Uncomment to allow public access:
    # APACHE_STATUS_PUBLIC: "true"
  healthcheck:
    test: ["CMD", "curl", "-f", "http://localhost:80/server-status?auto"]
    interval: 5s
    timeout: 30s
    retries: 5
  volumes:
    - ./.data/wordpress:/content
```

## Server Status Fields

The status page includes:

| Field | Description |
|-------|-------------|
| Uptime | Server running time |
| Total accesses | Number of requests served |
| CPU load | Current CPU utilization |
|ReqPerSec | Requests per second |
| BytesPerSec | Throughput in bytes/second |
| BytesPerReq | Average bytes per request |
| BusyWorkers | Active requests |
| IdleWorkers | Idle worker threads |

For detailed descriptions, visit Apache's [mod_status documentation](https://httpd.apache.org/docs/2.4/mod/mod_status.html).

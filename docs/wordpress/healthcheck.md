---
sidebar_position: 2
---

# Health Check

In order to ensure that your WordPress instance is running smoothly, it's important to implement health checks. This can be done by periodically checking the availability of the WordPress site and its database connection.

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.8.3-r0.0.4
  restart: always
  ports:
    - "80:80"
  environment:
    WORDPRESS_DB_HOST: mariadb:3306
    WORDPRESS_DB_USER: root
    WORDPRESS_DB_PASSWORD: secret
    WORDPRESS_DB_NAME: wordpress
    WORDPRESS_FS_METHOD: "direct"
    // highlight-next-line
    APACHE_STATUS: "true"
  volumes:
    - ./.data/wordpress:/content
  healthcheck:
    # Use server status to check if the server is running
    test: ["CMD", "curl", "-f", "http://localhost:80/server-status?auto"]
    interval: 5s
    timeout: 30s
    retries: 5
```

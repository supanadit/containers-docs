---
sidebar_position: 4
---

# WP Config

You can customize your WordPress configuration by setting environment variables in your Docker Compose file. Below is an example configuration that demonstrates how to set a custom table prefix and configure Redis caching for WordPress.

Simply use `WORDPRESS_` prefix followed by the constant name in uppercase to set the desired configuration. You don't need to modify the `wp-config.php` file directly; the container will automatically apply these settings based on the environment variables you provide.

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.8.3-r0.0.4
  restart: always
  ports:
    - "80:80"
  environment:
    CUSTOM_TABLE_PREFIX: "hello_world"
    WORDPRESS_WP_REDIS_HOST: "redis"
    WORDPRESS_WP_REDIS_CONFIG: |
      [
        'token' => '2sQODoCtbQiNVkLnNawGsyN30EHUFVJkI24hnnRbn5Xl5wXvwqV3YE0fXnZ5',
        'host' => 'redis',
        'port' => 6379,
        'database' => 0, // change for each site
        'split_alloptions' => true,
        'debug' => false,
      ]
  volumes:
    - ./.data/wordpress:/content
```

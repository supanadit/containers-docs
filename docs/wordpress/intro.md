---
id: wordpress-intro-doc
slug: /wordpress
title: Wordpress
---

:::warning Beta
This Wordpress container image is currently in beta. Most of the features are stable, but some features might still be under development or testing. Use with caution in production environments.
:::

# Wordpress

Wordpress image is first class supported in this container collection. In fact this is the first container image created in this collection. You can quickstart the Wordpress container with Docker compose using the following code snippet:

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
  volumes:
    - ./.data/wordpress:/content
```
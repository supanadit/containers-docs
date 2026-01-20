---
sidebar_position: 3
---

# Prometheus Exporter

To monitor your WordPress instance using Prometheus, you can enable the Apache Exporter in the Wordpress container. This exporter exposes Apache server metrics that can be scraped by Prometheus. To enable the Apache Exporter, set the `APACHE_EXPORTER` environment variable to `true` in your Docker Compose configuration as shown below:

```yaml
wordpress:
  image: ghcr.io/supanadit/containers/wordpress-apache:6.8.3-r0.0.4
  restart: always
  ports:
    - "80:80"
    - "9117:9117" # Apache Exporter
  environment:
    WORDPRESS_DB_HOST: mariadb:3306
    WORDPRESS_DB_USER: root
    WORDPRESS_DB_PASSWORD: secret
    WORDPRESS_DB_NAME: wordpress
    WORDPRESS_FS_METHOD: "direct"
    // highlight-next-line
    APACHE_EXPORTER: "true"
  volumes:
    - ./.data/wordpress:/content
```

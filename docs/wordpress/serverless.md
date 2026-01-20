---
sidebar_position: 1
---

# Serverless

This Wordpress container image supports serverless mode, allowing you to run Wordpress without relying on local storage. In serverless mode, content uploads are stored in a remote storage solution like GCS Fuse for example. But for Themes and Plugins, they must be included at build time. To enable serverless mode, you can set the `IS_STATELESS` environment variable to `true` in your Docker Compose configuration as shown below:

:::warning
The `theme` and `plugin` must be include in build time. You can create a custom Docker image that extends the base Wordpress image and includes your desired themes and plugins. So the `/content` folder only contains the `uploads` folder.
:::

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
    IS_STATELESS: "true"
  volumes:
    - ./.data/wordpress:/content
```

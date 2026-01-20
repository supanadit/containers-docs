# HTTPS Issue

It's very common to have HTTPS issues when running WordPress in a container behind a reverse proxy or load balancer that handles SSL termination. This is because WordPress may not be aware that the original request was made over HTTPS, leading to mixed content warnings and other related issues.

To resolve this issue, you can set the `IS_HTTPS` environment variable to `true` in your Docker Compose configuration. This will inform WordPress that the incoming requests are secure, allowing it to generate the correct URLs and avoid mixed content warnings.

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
    IS_HTTPS: "true"
  volumes:
    - ./.data/wordpress:/content
```

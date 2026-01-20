# Apache MPM

You can configure the Apache Multi-Processing Module (MPM) used by the WordPress container by setting the `APACHE_MPM` environment variable in your Docker Compose configuration. The available options are `prefork`, `worker`, and `event`.

If you don't know what the different MPMs are, here is a brief overview:

- **Prefork**: This MPM creates a separate process for each incoming connection. It is the most compatible MPM and is often used for applications that require thread safety, such as those using non-thread-safe libraries. However, it can consume more memory under high load due to the large number of processes.
- **Worker**: This MPM uses multiple threads per process to handle incoming connections. It is more memory efficient than Prefork and can handle more simultaneous connections. However, it requires that all libraries used by the application are thread-safe.
- **Event**: This MPM is similar to Worker but is optimized for handling keep-alive connections more efficiently. It uses a separate thread to manage keep-alive connections, allowing worker threads to focus on active requests. This can lead to better performance under high load with many keep-alive connections.

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
    APACHE_MPM: "worker" # Options: prefork, worker, event
  volumes:
    - ./.data/wordpress:/content
```

## Custom MPM Prefork Configuration

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
    APACHE_MPM: "prefork"
    // highlight-next-line
    APACHE_INCLUDE_CONFIG_MPM: "true"
    APACHE_CUSTOM_MPM_PREFORK: "true" # This will set to /usr/local/apache2/conf/extra/httpd-mpm.conf
    APACHE_MPM_PREFORK_START_SERVERS: "50"
    APACHE_MPM_PREFORK_MIN_SPARE_SERVERS: "10"
    APACHE_MPM_PREFORK_MAX_SPARE_SERVERS: "20"
    APACHE_MPM_PREFORK_MAX_REQUEST_WORKERS: "100"
    APACHE_MPM_PREFORK_MAX_REQUESTS_PER_CHILD: "5000"
  volumes:
    - ./.data/wordpress:/content
```

## Custom MPM Worker Configuration

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
    APACHE_MPM: "worker"
    // highlight-next-line
    APACHE_INCLUDE_CONFIG_MPM: "true"
    APACHE_CUSTOM_MPM_WORKER: "true" # This will set to /usr/local/apache2/conf/extra/httpd-mpm.conf
    APACHE_MPM_WORKER_START_SERVERS: "50"
    APACHE_MPM_WORKER_MIN_SPARE_THREADS: "10"
    APACHE_MPM_WORKER_MAX_SPARE_THREADS: "20"
    APACHE_MPM_WORKER_THREADS_PER_CHILD: "25"
    APACHE_MPM_WORKER_MAX_REQUEST_WORKERS: "100"
    APACHE_MPM_WORKER_MAX_CONNECTIONS_PER_CHILD: "9000"
  volumes:
    - ./.data/wordpress:/content
```

## Custom MPM Event Configuration

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
    APACHE_MPM: "event"
    // highlight-next-line
    APACHE_INCLUDE_CONFIG_MPM: "true"
    APACHE_CUSTOM_MPM_EVENT: "true" # This will set to /usr/local/apache2/conf/extra/httpd-mpm.conf
    APACHE_MPM_EVENT_START_SERVERS: "50"
    APACHE_MPM_EVENT_MIN_SPARE_SERVERS: "10"
    APACHE_MPM_EVENT_MAX_SPARE_SERVERS: "20"
    APACHE_MPM_EVENT_THREADS_PER_CHILD: "25"
    APACHE_MPM_EVENT_MAX_REQUEST_WORKERS: "100"
    APACHE_MPM_EVENT_MAX_CONNECTIONS_PER_CHILD: "9000"
  volumes:
    - ./.data/wordpress:/content
```

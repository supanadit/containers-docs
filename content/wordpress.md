# WordPress

WordPress on Apache with PHP, hardened and env-var driven: the entrypoint
generates `wp-config.php`, tunes `php.ini`, configures Apache (MPM, security
includes, compression) and supervises everything with
[ezx](https://github.com/supanadit/ezx). WordPress itself is the immutable
image content; your `wp-content` lives on the data volume.

## Quickstart

WordPress needs a database. Pair it with
[MariaDB](/mariadb) or the upstream `mariadb` image:

```yaml
services:
  db:
    image: mariadb:11.8
    environment:
      MARIADB_ROOT_PASSWORD: root
      MARIADB_DATABASE: wordpress
      MARIADB_USER: wordpress
      MARIADB_PASSWORD: wordpress
    volumes: [db-data:/var/lib/mysql]

  wordpress:
    image: ghcr.io/supanadit/containers/wordpress
    depends_on: [db]
    environment:
      WORDPRESS_DB_NAME: wordpress
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      WORDPRESS_DB_HOST: db:3306
    volumes:
      - wordpress-data:/opt/containers/data
    ports:
      - "80:80"

volumes:
  db-data:
  wordpress-data:
```

## Configuration

| Variable | Default | Description |
| --- | --- | --- |
| `WORDPRESS_DB_NAME` | *(required)* | Database name. |
| `WORDPRESS_DB_USER` | *(required)* | Database user. |
| `WORDPRESS_DB_PASSWORD` | *(required)* | Database password. |
| `WORDPRESS_DB_HOST` | *(required)* | Database host, e.g. `db:3306`. |
| `WORDPRESS_DB_CHARSET` | *(empty)* | DB charset (e.g. `utf8mb4`). |
| `CUSTOM_TABLE_PREFIX` | *(empty)* | `wp_` table prefix override. |
| `IS_HTTPS` | `false` | Site is served over HTTPS behind a proxy. |
| `IS_STATELESS` | `false` | Stateless mode: only `wp-content/uploads` is persisted. |
| `IS_PROTECT_XMLRPC` | `false` | Block `xmlrpc.php`. |
| `IS_PROTECT_WPCONFIG` | `false` | Deny direct access to `wp-config.php`. |
| `IS_SECURITY_BASELINE` | `false` | Umbrella for the safe hardening set (see below). |
| `IS_COMPRESSION` | `false` | gzip (mod_deflate) response compression. |
| `IS_EXPIRES_CACHING` | `false` | mod_expires browser caching for static assets. |
| `PHP_MEMORY_LIMIT` | *(default)* | `memory_limit` in `php.ini`, e.g. `256M`. |
| `PHP_UPLOAD_MAX_FILESIZE` | *(default)* | e.g. `64M`. |
| `APACHE_MPM` | `event` | MPM selection (`event`, `worker`, `prefork`). |
| `APACHE_LOW_MEMORY_MODE` | `false` | Conservative MPM defaults for small instances. |
| `APACHE_STATUS` | `false` | Enable `mod_status` on the Apache port. |
| `APACHE_EXPORTER` | `false` | Run apache_exporter for Prometheus metrics. |
| `APACHE_EXPORTER_PORT` | `9117` | apache_exporter listen port. |
| `SLEEP_MODE` | `false` | Start the supervisor but not Apache (debugging). |

### Security baseline

`IS_SECURITY_BASELINE=true` turns on the whole safe set; each flag can also be
toggled independently:

| Variable | Default | Description |
| --- | --- | --- |
| `IS_HIDE_SERVER_INFO` | = baseline | Hide `Server` version tokens. |
| `IS_DISABLE_DIRLIST` | = baseline | Disable directory listings. |
| `IS_DISABLE_TRACE` | = baseline | Reject HTTP TRACE. |
| `IS_SECURITY_HEADERS` | = baseline | Send standard security headers. |
| `IS_BLOCK_SENSITIVE_FILES` | = baseline | Block access to dotfiles and sensitive paths. |
| `IS_BLOCK_PHP_IN_UPLOADS` | = baseline | Refuse to execute PHP under `uploads/`. |
| `IS_SLOWLORIS_PROTECT` | = baseline | RequestTimeout/KeepAlive limits against slow-loris. |
| `IS_PROTECT_WPLOGIN` | `false` | Restrict `wp-login.php` to `WP_LOGIN_ALLOW_IPS`. |
| `IS_HSTS` | `false` | HSTS header — always explicit opt-in, never part of the umbrella. |
| `TRUSTED_PROXY_IPS` | *(empty)* | Proxy IPs trusted for `X-Forwarded-*` handling. |

## Health

The container's HEALTHCHECK probes the ezx health API on loopback port 8080 —
`/health/comprehensive` must report `"healthy":true`, which composites a TCP
check on Apache's port. The API is intentionally loopback-only; publish port
80 for the site itself.

```bash
curl -s http://localhost:80/            # the site
docker exec wordpress curl -s http://127.0.0.1:8080/health/comprehensive
```

See [Health & readiness](/health) for the composite endpoint contract.

## Stateless mode

By default the whole `wp-content` tree is persisted to
`/opt/containers/data`. With `IS_STATELESS=true` only `wp-content/uploads` is
persisted, so the image can be replaced freely (immutable-content deploys,
multiple replicas). Extra files (drop-in plugins, mu-plugins) can be injected
at boot with `STATELESS_FILE_<name>=<filename>` variables: the entrypoint
copies `/content/stateless/<filename>` into `wp-content` when it exists.

```yaml
wordpress:
  environment:
    WORDPRESS_DB_HOST: db:3306
    # ...
    IS_STATELESS: "true"
  volumes:
    - wordpress-uploads:/opt/containers/data
```

> [!TIP]
> Keep `/opt/containers/data` on a volume in every mode — in stateless mode it
> holds the uploads; in stateful mode it holds the whole `wp-content`.

## Performance tuning

Compression and browser caching are opt-in so existing deployments are
unaffected:

```yaml
environment:
  IS_COMPRESSION: "true"
  IS_EXPIRES_CACHING: "true"
  APACHE_BROTLI: "true"            # better ratio, more CPU
  PHP_OPCACHE_MEMORY: "192"        # opcache shm size in MB
  PHP_UPLOAD_MAX_FILESIZE: "64M"
```

`APACHE_EXPIRES_ASSETS` and `APACHE_EXPIRES_HTML` accept Apache
`mod_expires` syntax (e.g. `access plus 1 year`); `APACHE_COMPRESS_TYPES`
overrides the compressed MIME-type list.

## Monitoring

Set `APACHE_STATUS=true` for `mod_status` (server-status on the Apache port)
and `APACHE_EXPORTER=true` to run apache_exporter on port 9117 for scraping by
[Prometheus](/prometheus). Keep `mod_status` internal unless you also set
`APACHE_STATUS_PUBLIC=true`.

## Next steps

- [Configuration](/configuration) — how env vars become `wp-config.php`,
  `php.ini` and `httpd.conf`.
- [Volumes](/volumes) — the `/opt/containers/*` layout.
- [MariaDB](/mariadb) — the database this image pairs with.

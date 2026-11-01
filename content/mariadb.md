# MariaDB

MariaDB 11.8 built from source with Galera 26.4 for multi-master clustering.
The entrypoint generates `my.cnf` from environment variables at boot,
creates the database and application user on first start, and can schedule
mariabackup jobs with optional S3/SFTP upload — no init scripts to mount.

## Quickstart

```bash
docker run -d \
  --name mariadb \
  -e MARIADB_ROOT_PASSWORD=secret \
  -e MARIADB_DATABASE=app \
  -e MARIADB_USER=app \
  -e MARIADB_PASSWORD=app-secret \
  -v mariadb-data:/opt/containers/data \
  -p 3306:3306 \
  ghcr.io/supanadit/containers/mariadb
```

Or with Compose:

```yaml
services:
  mariadb:
    image: ghcr.io/supanadit/containers/mariadb
    environment:
      MARIADB_ROOT_PASSWORD: secret
      MARIADB_DATABASE: app
      MARIADB_USER: app
      MARIADB_PASSWORD: app-secret
    volumes:
      - mariadb-data:/opt/containers/data
    ports:
      - "3306:3306"

volumes:
  mariadb-data:
```

## Configuration

| Variable | Default | Description |
| --- | --- | --- |
| `MARIADB_ROOT_PASSWORD` | *(empty)* | Root password. When empty, `root@localhost` keeps unix_socket auth (combine with `MARIADB_ALLOW_EMPTY_PASSWORD=yes` for the socket-less case). |
| `MARIADB_DATABASE` | *(empty)* | Database created on first boot. |
| `MARIADB_USER` / `MARIADB_PASSWORD` | *(empty)* | Application account created with full grants on `MARIADB_DATABASE`. |
| `MARIADB_ALLOW_EMPTY_PASSWORD` | `no` | `yes` permits an empty root password (development only). |
| `MARIADB_PORT` | `3306` | Listen port. |
| `MARIADB_BIND_ADDRESS` | `0.0.0.0` | Listen address. |
| `MARIADB_MAX_CONNECTIONS` | `151` | `max_connections`. |
| `MARIADB_MAX_ALLOWED_PACKET` | `256M` | `max_allowed_packet`. |
| `MARIADB_CHARACTER_SET_SERVER` | `utf8mb4` | Default character set. |
| `MARIADB_COLLATION_SERVER` | `utf8mb4_unicode_ci` | Default collation. |
| `MARIADB_INNODB_BUFFER_POOL_SIZE` | `256M` | `innodb_buffer_pool_size` — raise this for real workloads. |
| `MARIADB_INNODB_LOG_FILE_SIZE` | `64M` | `innodb_log_file_size`. |
| `MARIADB_SLOW_QUERY_LOG` | `1` | Slow query log on by default; `MARIADB_LONG_QUERY_TIME` (default `2` s) sets the threshold. |
| `MARIADB_LOG_BIN` | `1` | Binary logging on by default, expiring after `MARIADB_EXPIRE_LOGS_DAYS` (default `7`) days. |
| `MARIADB_TIMEZONE` | `SYSTEM` | Server timezone; timezone tables are preloaded at build time. |

### TLS

| Variable | Default | Description |
| --- | --- | --- |
| `MARIADB_SSL_ENABLE` | `false` | Require TLS. The 11.8 client requests TLS by default, so servers running with `ssl=0` reject TLS clients. |
| `MARIADB_SSL_CERT_DIR` | `/opt/containers/config/ssl` | Directory holding `ca.crt`, server cert and key. |

### Galera cluster

| Variable | Default | Description |
| --- | --- | --- |
| `MARIADB_GALERA_ENABLE` | `false` | Enable the WSREP/Galera provider (bundled as `libgalera_smm.so`). |
| `MARIADB_GALERA_CLUSTER_NAME` | `galera_cluster` | Cluster name; every node must match. |
| `MARIADB_GALERA_SEEDS` | *(empty)* | Comma-separated `gcomm://` seed list of peer nodes. |
| `MARIADB_GALERA_SST_METHOD` | `mariabackup` | State snapshot transfer method. |
| `MARIADB_GALERA_SERVER_ID` | `1` | Unique server id per node. |
| `MARIADB_NODE_ADDRESS` | *(empty)* | This node's address for cluster communication. |

### Backups

| Variable | Default | Description |
| --- | --- | --- |
| `MARIADB_BACKUP_ENABLE` | `false` | Register the scheduled backup node. |
| `MARIADB_BACKUP_METHOD` | `mariabackup` | Backup engine. |
| `MARIADB_BACKUP_SCHEDULE` | `0 2 * * *` | Cron schedule for full backups. |
| `MARIADB_BACKUP_INCR_SCHEDULE` | *(empty)* | Optional cron schedule for incremental backups extending the newest chain. |
| `MARIADB_BACKUP_RETENTION_DAYS` | `7` | Local retention window. |
| `MARIADB_BACKUP_S3_BUCKET` / `_REGION` / `_ENDPOINT` | *(empty)* / `us-east-1` / *(empty)* | Upload backups to S3-compatible storage. |
| `MARIADB_BACKUP_SFTP_HOST` / `_USER` / `_KEY` / `_DEST` | *(empty)* / `backup` / *(empty)* / `/backups` | Upload backups over SFTP. |

## Health

`/health` (composite) and `/health/comprehensive` (detail) are registered on
the loopback health server (`EZX_HEALTH_ADDR`, `127.0.0.1:8080` in this
image). The composite check runs a real `mariadb-admin ping` over the unix
socket; the detail view also reports Galera status, disk headroom and
process/zombie state.

```bash
curl -s http://127.0.0.1:8080/health/comprehensive
# {"healthy":true,"checks":{"mariadb":true,"galera":true,"disk":true,"process":true}}
```

## MaxScale service account

When both `MAXSCALE_SERVICE_USER` and `MAXSCALE_SERVICE_PASSWORD` are set,
the entrypoint creates that account with the grants MaxScale's monitor
needs — ready for [MaxScale](/maxscale) to proxy this server.

> [!TIP]
> Put MaxScale in front of a Galera cluster for read/write splitting: set
> the same `MAXSCALE_SERVICE_USER`/`PASSWORD` on every MariaDB node, then
> point MaxScale's monitor at the node list — see
> [Configuration](/configuration) for the general env-var model.

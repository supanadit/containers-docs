# PostgreSQL

PostgreSQL 13 with the extensions and tooling that production deployments
actually need, baked into one image: PgBouncer for connection pooling,
pgBackRest for backups, Patroni for HA, and a curated extension set
(pgAudit, Citus, pg_cron, pg_repack and friends). Everything is configured
through environment variables — the entrypoint generates
`postgresql.conf` and `pg_hba.conf` at boot, so there is no config to mount.

## Quickstart

```bash
docker run -d \
  --name postgres \
  -e POSTGRES_DB=app \
  -e POSTGRES_PASSWORD=secret \
  -v postgres-data:/opt/containers/data \
  -p 5432:5432 \
  ghcr.io/supanadit/containers/postgresql
```

Or with Compose, plus PgBouncer on port 6432:

```yaml
services:
  postgres:
    image: ghcr.io/supanadit/containers/postgresql
    environment:
      POSTGRES_DB: app
      POSTGRES_PASSWORD: secret
      PGBOUNCER_ENABLE: "true"
    volumes:
      - postgres-data:/opt/containers/data
    ports:
      - "5432:5432"
      - "6432:6432"

volumes:
  postgres-data:
```

Data lives in `/opt/containers/data` (this is `PGDATA`), generated config in
`/opt/containers/config`, and pgBackRest output in `/opt/containers/backup` —
see [Volumes](/volumes).

## Configuration

| Variable | Default | Description |
| --- | --- | --- |
| `POSTGRES_DB` | `postgres` | Database created on first boot. |
| `POSTGRES_USER` | `postgres` | Superuser name (also the OS user). |
| `POSTGRES_PASSWORD` | *(unset)* | Superuser password. With the default `POSTGRES_HOST_AUTH_METHOD=trust` it is optional, but set it for anything real. |
| `POSTGRES_HOST_AUTH_METHOD` | `trust` | `pg_hba.conf` auth method for host connections (`md5`, `scram-sha-256`, ...). |
| `POSTGRESQL_PORT` | `5432` | Port PostgreSQL listens on. |
| `POSTGRESQL_TIMEZONE` | `UTC` | Server timezone. |
| `POSTGRES_INITDB_ARGS` | *(empty)* | Extra arguments passed to `initdb` on first boot. |
| `POSTGRES_INITDB_WALDIR` | *(empty)* | Place `pg_wal` on a separate path (e.g. a dedicated volume). |
| `LOG_LEVEL` | `INFO` | Entrypoint log verbosity. |
| `SLEEP_MODE` | `false` | Validate and set up directories, then idle — for debugging the entrypoint. |

### Connection pooling (PgBouncer)

Set `PGBOUNCER_ENABLE=true` to start PgBouncer alongside PostgreSQL.

| Variable | Default | Description |
| --- | --- | --- |
| `PGBOUNCER_ENABLE` | `false` | Run PgBouncer in the same container. |
| `PGBOUNCER_LISTEN_PORT` | `6432` | PgBouncer listen port. |
| `PGBOUNCER_POOL_MODE` | `transaction` | `session`, `transaction` or `statement`. |
| `PGBOUNCER_MAX_CLIENT_CONN` | `100` | Maximum client connections. |
| `PGBOUNCER_DEFAULT_POOL_SIZE` | `20` | Connections kept per user/database pair. |
| `PGBOUNCER_AUTH_TYPE` | `md5` | Client authentication type. |

### Replication and HA

| Variable | Default | Description |
| --- | --- | --- |
| `HA_MODE` | *(empty)* | Enables the native streaming-replication code paths when set. |
| `REPLICATION_ROLE` | *(empty)* | `primary` or `standby`. |
| `PRIMARY_HOST` / `PRIMARY_PORT` | *(empty)* / `5432` | Where a standby finds its primary. |
| `REPLICATION_USER` / `REPLICATION_PASSWORD` | `replicator` / *(empty)* | Replication credentials. |
| `REPLICATION_SLOT` | *(empty)* | This standby's physical replication slot. |
| `REPLICATION_SLOTS` | *(empty)* | Comma-separated slots a primary ensures exist, one per standby. |
| `PATRONI_ENABLE` | `false` | Run Patroni instead of a bare `postgres` node (uses `PATRONI_*` variables and an optional `ETCD_*` DCS). |
| `CITUS_ENABLE` | `false` | Load the Citus extension; `CITUS_ROLE` (`coordinator`/`worker`) shapes the config. |

### Backups (pgBackRest)

| Variable | Default | Description |
| --- | --- | --- |
| `PGBACKREST_ENABLE` | `false` | Generate `/etc/pgbackrest.conf` and enable WAL archiving. |
| `PGBACKREST_STANZA` | `default` | pgBackRest stanza name. |
| `PGBACKREST_AUTO_ENABLE` | `false` | Schedule automatic full/incremental backups. |
| `PGBACKREST_ARCHIVE_ENABLE` | `true` | Enable WAL archiving to the repository. |
| `PGBACKREST_REPO_TYPE` | *(empty)* | Repository type: `s3`, `gcs` or `sftp` (default is a local repo under `/opt/containers/backup`). |

## Health

The entrypoint registers `/health` (composite) and `/health/comprehensive`
(detail) on a loopback health server (`EZX_HEALTH_ADDR`, `127.0.0.1:8080` in
this image) before PostgreSQL starts, so the endpoint reflects real
readiness — including PgBouncer and Patroni when enabled.

```bash
curl -s http://127.0.0.1:8080/health/comprehensive
# {"healthy":true,"checks":{...}}
```

## Extensions

The image bundles a curated extension set, built from pinned sources in
[forge.toml](https://github.com/supanadit/containers/blob/main/docker/postgresql/forge.toml):
pgAudit, pg_stat_monitor, Citus, pg_cron, hypopg, pg_repack, pgmetrics and
the decoderbufs logical-decoding plugin. Enable them per-database as usual:

```sql
CREATE EXTENSION pg_stat_monitor;
CREATE EXTENSION pg_cron;
```

> [!TIP]
> For a full HA stack, pair this image with Patroni (`PATRONI_ENABLE=true`)
> and take backups with pgBackRest — see
> [Configuration](/configuration) for how the env vars become
> `postgresql.conf` and `pgbackrest.conf`.

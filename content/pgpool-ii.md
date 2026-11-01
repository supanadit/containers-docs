# PgPool-II

PgPool-II 4.6 in front of your [PostgreSQL](/docs/postgresql) fleet:
connection pooling, read/write load balancing, streaming-replication
awareness and watchdog-based HA for the pool itself. The entrypoint
generates `pgpool.conf` and `pool_hba.conf` from environment variables and
builds `pool_passwd` with `pg_enc`, so PgPool can authenticate to backends
using SCRAM — the part a hand-rolled setup usually gets wrong.

## Quickstart

`PGPOOL_BACKENDS` is the only required variable — a comma-separated list of
`host:port` backends:

```bash
docker run -d \
  --name pgpool \
  -e PGPOOL_BACKENDS=172.10.10.5:5432,172.10.10.6:5432 \
  -e PGPOOL_BACKEND_USER=postgres \
  -e PGPOOL_BACKEND_PASSWORD=secret \
  -v pgpool-config:/opt/containers/config \
  -p 5432:5432 \
  ghcr.io/supanadit/containers/pgpool-ii
```

Or with Compose:

```yaml
services:
  pgpool:
    image: ghcr.io/supanadit/containers/pgpool-ii
    environment:
      PGPOOL_BACKENDS: "pg1:5432,pg2:5432"
      PGPOOL_BACKEND_USER: postgres
      PGPOOL_BACKEND_PASSWORD: secret
    ports:
      - "5432:5432"
```

Clients connect to PgPool as if it were PostgreSQL; the PCP control
interface listens on `PGPOOL_PCP_PORT` (default `9898`).

## Configuration

| Variable | Default | Description |
| --- | --- | --- |
| `PGPOOL_BACKENDS` | *(required)* | Comma-separated `host:port` backend list, in follow-master order. |
| `PGPOOL_BACKEND_WEIGHTS` | *(empty)* | Per-backend load-balancing weights. |
| `PGPOOL_BACKEND_FLAGS` | *(empty)* | Per-backend flags (e.g. mark the primary / disable). |
| `PGPOOL_PORT` | `5432` | Client-facing listen port. |
| `PGPOOL_PCP_PORT` | `9898` | PCP control protocol port. |
| `PGPOOL_BACKEND_USER` / `PGPOOL_BACKEND_PASSWORD` | `postgres` / *(empty)* | Credentials PgPool uses to reach backends. |
| `PGPOOL_LOAD_BALANCE_MODE` | `on` | Route reads to standbys. |
| `PGPOOL_MASTER_SLAVE_MODE` | `on` | Master/slave operation; `PGPOOL_MASTER_SLAVE_SUB_MODE` defaults to `stream` (streaming replication). |
| `PGPOOL_NUM_INIT_CHILDREN` | `32` | Number of pgpool child processes (each serves many clients). |
| `PGPOOL_MAX_POOL` | `4` | Cached connections per child. |
| `PGPOOL_CONNECTION_LIFE_TIME` | `0` | Seconds before a pooled backend connection is dropped (`0` = never). |
| `PGPOOL_ENABLE_POOL_HBA` | `false` | Enable `pool_hba.conf` client authentication. |

### Health and SR checks

| Variable | Default | Description |
| --- | --- | --- |
| `PGPOOL_HEALTH_CHECK_PERIOD` | `10` | Seconds between backend health checks (`0` disables). |
| `PGPOOL_HEALTH_CHECK_USER` / `_PASSWORD` | backend credentials | Credentials for the health check. |
| `PGPOOL_HEALTH_CHECK_MAX_RETRIES` / `_RETRY_DELAY` | `0` / `1` | Retries before a backend is failed over. |
| `PGPOOL_SR_CHECK_PERIOD` | `10` | Seconds between streaming-replication delay checks (`0` disables). |
| `PGPOOL_DELAY_THRESHOLD` | `0` | Standbys further behind than this (bytes) are not selected for reads. |

### Watchdog (pool HA)

| Variable | Default | Description |
| --- | --- | --- |
| `PGPOOL_ENABLE_WATCHDOG` | `false` | Enable watchdog; requires `PGPOOL_WD_HOSTNAME` and `PGPOOL_DELEGATE_IP`. |
| `PGPOOL_DELEGATE_IP` | *(empty)* | Virtual IP failed over between pgpool nodes. |
| `PGPOOL_WD_LIFECHECK_METHOD` | `heartbeat` | `heartbeat`, `external` or `quorum`. |
| `PGPOOL_WD_PRIORITY` | `1` | Higher wins the delegate IP. |

### Memory query cache

| Variable | Default | Description |
| --- | --- | --- |
| `PGPOOL_MEMORY_CACHE_MODE` | `false` | Enable the in-memory query cache. |
| `PGPOOL_MEMQCACHE_MAXCACHE` | `50` | Maximum cache size (MiB). |
| `PGPOOL_MEMQCACHE_EXPIRE_TIME` | `10000` | Cache TTL in seconds. |

### TLS

| Variable | Default | Description |
| --- | --- | --- |
| `PGPOOL_SSL` | `false` | Serve TLS to clients. |
| `PGPOOL_SSL_KEY` / `PGPOOL_SSL_CERT` | *(empty)* | Paths to the server key and certificate. |
| `PGPOOL_KEY` / `PGPOOL_KEY_FILE` | *(empty)* | AES key (or key file) for `pool_passwd` encryption; one is generated under `/opt/containers/config` if unset. |

## Health

The image's Docker `HEALTHCHECK` probes `/health/comprehensive` on the
loopback health server (`EZX_HEALTH_ADDR`, `127.0.0.1:8080`). A healthy
answer means more than a listening port: the check runs a real `SELECT 1`
through PgPool to the backend, so authentication problems surface as
unhealthy, not as silent client errors.

```bash
curl -s http://127.0.0.1:8080/health/comprehensive
# {"healthy":true,"checks":{"pgpool":true,"backendAuth":true}}
```

> [!NOTE]
> The comprehensive endpoint needs `PGPOOL_HEALTH_CHECK_PASSWORD` (it falls
> back to `PGPOOL_BACKEND_PASSWORD`) to run the backend round-trip.

> [!TIP]
> Point PgPool at a replicated fleet from the
> [PostgreSQL](/docs/postgresql) image (`HA_MODE` + `REPLICATION_ROLE`) and
> set `PGPOOL_BACKEND_FLAGS` to pin the primary — see
> [Configuration](/docs/configuration) for the general env-var model.

# MaxScale

MariaDB MaxScale 24.02 as a stateless proxy in front of your
[MariaDB](/docs/mariadb) servers: one listener that splits writes from
reads, a monitor that tracks the topology, and `maxctrl` over a REST API
for everything else. The entrypoint generates `maxscale.cnf` from
environment variables at boot, so a working read/write-splitting proxy is
about six variables.

## Quickstart

```bash
docker run -d \
  --name maxscale \
  -e MAXSCALE_SERVICE_USER=maxscale \
  -e MAXSCALE_SERVICE_PASSWORD=maxscale \
  -e MAXSCALE_SERVICE_READWRITE_ROUTES=db1:3306,db2:3306 \
  -e MAXSCALE_SERVICE_READCONN_ROUTES=db2:3306 \
  -e MAXSCALE_MONITOR_MARIADB_MONITOR_SERVERS=db1:3306,db2:3306 \
  -e EZX_HEALTH_ADDR=127.0.0.1:8080 \
  -v maxscale-data:/opt/containers/data \
  -p 3306:3306 -p 8989:8989 \
  ghcr.io/supanadit/containers/maxscale
```

Or with Compose, in front of a MariaDB server:

```yaml
services:
  maxscale:
    image: ghcr.io/supanadit/containers/maxscale
    environment:
      MAXSCALE_SERVICE_USER: maxscale
      MAXSCALE_SERVICE_PASSWORD: maxscale
      MAXSCALE_SERVICE_READWRITE_ROUTES: "db:3306"
      MAXSCALE_SERVICE_READCONN_ROUTES: "db:3306"
      MAXSCALE_MONITOR_MARIADB_MONITOR_SERVERS: "db:3306"
      EZX_HEALTH_ADDR: "127.0.0.1:8080"
    ports:
      - "3306:3306"
      - "8989:8989"

  db:
    image: ghcr.io/supanadit/containers/mariadb
    environment:
      MARIADB_ROOT_PASSWORD: secret
      MAXSCALE_SERVICE_USER: maxscale
      MAXSCALE_SERVICE_PASSWORD: maxscale
```

The `MAXSCALE_SERVICE_USER`/`PASSWORD` must match an account with monitor
grants on the backends — set the same pair on your MariaDB containers and
the entrypoint creates it for you.

## Configuration

| Variable | Default | Description |
| --- | --- | --- |
| `MAXSCALE_SERVICE_READWRITE_ROUTES` | `localhost:3306` | Comma-separated backends for the read-write (writes) route. |
| `MAXSCALE_SERVICE_READCONN_ROUTES` | `localhost:3306` | Backends for the read-connection (reads) route. |
| `MAXSCALE_MONITOR_MARIADB_MONITOR_SERVERS` | `localhost:3306` | Servers the MariaDB monitor watches for topology/failover. |
| `MAXSCALE_SERVICE_USER` / `MAXSCALE_SERVICE_PASSWORD` | `maxscale` / `maxscale` | Monitor account MaxScale uses on the backends. |
| `MAXSCALE_PORT` | `3306` | Port of the client-facing read-write listener. |
| `MAXSCALE_REST_PORT` | `8989` | REST API port (`maxctrl` talks here). |
| `MAXSCALE_THREADS` | `auto` | Worker threads (`auto` sizes from CPUs). |
| `MAXSCALE_CONFIG_DIR` | `/opt/containers/config` | Config directory; the generated file is `/etc/maxscale.cnf.d/generated.cnf`. |
| `MAXSCALE_DATA_DIR` | `/opt/containers/data` | Persistent state (runtime data, `maxctrl` cache). |
| `MAXSCALE_LOG_DIR` | `/opt/containers/logs` | Log output (rotated by ezx). |
| `MAXSCALE_READY_MAX_ATTEMPTS` / `_INTERVAL` | `30` / `1` | Readiness probe attempts and seconds between them. |
| `SLEEP_MODE` (or `MAXSCALE_SLEEP_MODE`) | `false` | Validate config and idle instead of starting — for debugging the entrypoint. |

## Health

`/health` (composite) and `/health/comprehensive` (detail) are registered
on the loopback health server (`EZX_HEALTH_ADDR`). The composite check
requires the REST API on `MAXSCALE_REST_PORT` to answer; the detail view
also reports process state, disk headroom on the data mount and zombies.

```bash
curl -s http://127.0.0.1:8080/health/comprehensive
# {"healthy":true,"checks":{"maxscale":true,"process":true,"disk":true,"zombies":true}}
```

Inspect the running topology with `maxctrl` (or plain HTTP):

```bash
docker exec maxscale maxctrl list servers
curl -s --user admin:mariadb http://127.0.0.1:8989/v1/maxscale
```

> [!NOTE]
> `EZX_HEALTH_ADDR` is not preset in this image (unlike
> [PostgreSQL](/docs/postgresql) and MariaDB) — set it to `127.0.0.1:8080`
> to get the `/health` endpoints, otherwise the container still starts with
> a warning.

> [!TIP]
> MaxScale shines in front of a [Galera cluster](/docs/mariadb#galera-cluster):
> point all three route/monitor variables at the node list and let the
> monitor route around a failed node — see
> [Configuration](/docs/configuration) for the general env-var model.

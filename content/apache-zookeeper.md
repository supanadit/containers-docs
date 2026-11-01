# Apache ZooKeeper

Apache ZooKeeper 3.9, env-var driven like every container in this collection:
the entrypoint renders `zoo.cfg` from typed variables, supervises the server
with [ezx](https://github.com/supanadit/ezx), and exposes the shared composite
health endpoint. Use it when you need ZooKeeper itself (legacy Kafka, other
coordinators); Kafka 3.9+ images here run KRaft and do not need it.

## Quickstart

```bash
docker run -d \
  --name zookeeper \
  -v zookeeper-data:/opt/containers/data \
  -p 2181:2181 \
  ghcr.io/supanadit/containers/apache-zookeeper
```

Verify from another container:

```bash
echo ruok | nc zookeeper 2181   # imok
curl -s http://localhost:8080/health
```

## Configuration

| Variable | Default | Description |
| --- | --- | --- |
| `ZOOKEEPER_CLIENT_PORT` | `2181` | Client-facing port. |
| `ZOOKEEPER_TICK_TIME` | `2000` | Base tick unit in ms; heartbeats and timeouts are multiples of it. |
| `ZOOKEEPER_MAX_CLIENT_CNXNS` | `60` | Max concurrent connections per client IP. |
| `ZOOKEEPER_ADMIN_ENABLE_SERVER` | `false` | Enables the Jetty admin server (`ZOOKEEPER_ADMIN_SERVER_PORT`). |
| `ZOOKEEPER_ADMIN_SERVER_PORT` | `8081` | Admin server port — moved off 8080, which belongs to the ezx health API. |
| `ZOOKEEPER_DATA_DIR` | `/opt/containers/data` | Data dir; the transaction log lives in its `log/` subdirectory. |
| `ZOOKEEPER_CONFIG_DIR` | `/opt/containers/config` | Where `zoo.cfg` is generated (also honours `ZOOCFGDIR`). |
| `ZOOKEEPER_LOG_DIR` | `/opt/containers/logs` | Rotated application logs. |
| `ZOOKEEPER_USER` / `ZOOKEEPER_GROUP` | `zookeeper` | Account the server drops to. |
| `SLEEP_MODE` | `false` | Start the supervisor but not the server (debugging). |

The container owns the data and log directories; ZooKeeper's own `ZOO_*`
tuning knobs stay in `zoo.cfg` and are rendered from the variables above.

## Health

The health check probes TCP on the client port, so `/health` reports healthy
as soon as ZooKeeper accepts client connections. The ezx API listens on port
8080:

```bash
curl -s http://localhost:8080/health
```

See [Health & readiness](/docs/health) for the full endpoint contract.

## Ensemble (clustering)

A three-node ensemble is the smallest fault-tolerant configuration. The
container has no dedicated clustering variables: ZooKeeper ensembles are
declared with standard `server.N` lines, so append them by mounting a
drop-in fragment alongside the generated config in
`/opt/containers/config`:

```yaml
services:
  zk-1:
    image: ghcr.io/supanadit/containers/apache-zookeeper
    volumes:
      - zk-1-data:/opt/containers/data
      - ./zk-1.cfg:/opt/containers/config/zoo.cfg.d/server.cfg:ro
```

> [!TIP]
> Keep an odd number of nodes (3 or 5). The quorum needs a majority to elect a
> leader, and the health endpoint of every node stays degraded until one is
> elected.

## Next steps

- [Configuration](/docs/configuration) — how env vars become config files.
- [Volumes](/docs/volumes) — the `/opt/containers/*` layout.
- [Apache Kafka](/docs/apache-kafka) — runs KRaft, no ZooKeeper required.

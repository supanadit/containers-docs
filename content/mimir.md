# Mimir

Grafana Mimir is a horizontally scalable, long-term store for Prometheus
metrics. Mimir's full YAML is notoriously long; this image collapses it to a
few `MIMIR_*` / `GRAFANA_MIMIR_*` environment variables, renders `mimir.yaml`
at boot, and supervises the server with
[ezx](https://github.com/supanadit/ezx). Point
[Prometheus](/prometheus) remote-write at it and you have durable
metrics with a PromQL API.

## Quickstart

```bash
docker run -d \
  --name mimir \
  -v mimir-data:/opt/containers/data \
  -p 8080:8080 \
  ghcr.io/supanadit/containers/grafana-mimir
```

The generated config lands at `/opt/containers/config/mimir.yaml`, and all
state directories (TSDB, bucket-store sync, compactor, ruler) live in
subdirectories of the data volume — Mimir refuses overlapping directories, so
each gets its own.

## Configuration

| Variable                             | Default                              | Description                                       |
| ------------------------------------ | ------------------------------------ | ------------------------------------------------- |
| `MIMIR_TARGET`                       | `all`                                | Component set: `all`, `distributor`, `ingester`, `querier`, `query-frontend`, `compactor`, `store-gateway`, `ruler`, ... |
| `MIMIR_HTTP_PORT`                    | `8080`                               | HTTP API port.                                    |
| `GRAFANA_MIMIR_CONFIG`               | `/opt/containers/config/mimir.yaml`  | Generated config file.                            |
| `GRAFANA_MIMIR_DATA_DIR`             | `/opt/containers/data`               | State root for all Mimir directories.             |
| `MIMIR_TSDB_DIR`                     | `<data>/tsdb`                        | Local TSDB (ingester) directory.                  |
| `MIMIR_SYNC_DIR`                     | `<data>/tsdb-sync`                   | Bucket-store sync directory.                      |
| `MIMIR_COMPACTOR_DIR`                | `<data>/compactor`                   | Compactor data directory.                         |
| `MIMIR_RULER_DIR`                    | `<data>/ruler`                       | Ruler data directory.                             |
| `MIMIR_STORAGE_PREFIX`               | `blocks`                             | Prefix for block storage.                         |
| `MIMIR_MEMBER_LIST`                  | *(unset)*                            | Comma-separated memberlist peers; enables clustering. |
| `MIMIR_INGESTER_REPLICATION_FACTOR`  | number of members (or `1`)           | Ingester ring replication factor.                 |
| `GRAFANA_MIMIR_EXTRA_ARGS`           | *(unset)*                            | Extra CLI arguments.                              |
| `MIMIR_ACTIVITY_FILE`                | `<data>/metrics-activity.log`        | Activity tracker file.                            |
| `GRAFANA_MIMIR_READY_MAX_ATTEMPTS`   | `30`                                 | Readiness probe attempts.                         |
| `GRAFANA_MIMIR_READY_ATTEMPT_INTERVAL`| `1`                                 | Seconds between probe attempts.                   |

The directory defaults exist because Mimir's own defaults (`./tsdb-sync`,
`./data-compactor`, `./metrics-activity.log`) are relative to a working
directory the unprivileged `mimir` user cannot write — the container pins them
onto the data volume so the process starts first try.

## Clustering

Set `MIMIR_MEMBER_LIST` to enable memberlist gossip between replicas, and split
components across containers with `MIMIR_TARGET`:

```yaml
services:
  mimir:
    image: ghcr.io/supanadit/containers/grafana-mimir
    environment:
      MIMIR_MEMBER_LIST: mimir-1,mimir-2,mimir-3
```

The ingester replication factor defaults to the number of members, so a
three-node ring replicates each series three times unless you override
`MIMIR_INGESTER_REPLICATION_FACTOR`.

## Remote write from Prometheus

Prometheus needs its remote-write receiver enabled; then aim it at Mimir:

```yaml
services:
  prometheus:
    image: ghcr.io/supanadit/containers/prometheus
    environment:
      PROMETHEUS_ENABLE_WEB_REMOTE_WRITE_RECEIVER: "true"

  mimir:
    image: ghcr.io/supanadit/containers/grafana-mimir
```

In `prometheus.yml`:

```yaml
remote_write:
  - url: http://mimir:8080/api/v1/push
```

> [!TIP]
> Query Mimir from [Grafana](/grafana) as a Prometheus datasource at
> `http://mimir:8080/prometheus` — Mimir speaks PromQL, so no extra setup.

## Health

The composite health endpoint runs on the shared health server. Mimir itself
must answer `/ready` on `MIMIR_HTTP_PORT`; the composite also checks the
process, free disk on the data volume and zombie reaping.

```bash
curl -s http://localhost:8080/ready
curl -s http://localhost:8080/health
curl -s http://localhost:8080/mimir/ready
```

(When Mimir and the health server share port 8080, the last two URLs resolve
to the same server.)

## Next steps

- [Prometheus](/prometheus) scrapes and pushes into Mimir.
- Prefer Thanos' object-storage approach? See [Thanos](/thanos).

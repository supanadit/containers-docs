# Loki

Grafana Loki is a log aggregation system that indexes labels instead of full
log lines, making it cheap to run. This image reduces Loki's large YAML
configuration to a handful of `GRAFANA_LOKI_*` environment variables: at boot
the entrypoint renders the full `loki.yaml` (schema, storage, ring, compactor,
ruler) deterministically and starts Loki under
[ezx](https://github.com/supanadit/ezx) supervision.

## Quickstart

Single-node filesystem Loki:

```bash
docker run -d \
  --name loki \
  -v loki-data:/opt/containers/data \
  -p 3100:3100 \
  ghcr.io/supanadit/containers/grafana-loki
```

Query it and check readiness:

```bash
curl -s http://localhost:3100/ready
curl -s http://localhost:8080/health
```

## Configuration

| Variable                            | Default                                | Description                                       |
| ----------------------------------- | -------------------------------------- | ------------------------------------------------- |
| `GRAFANA_LOKI_CONFIG`               | `/opt/containers/config/loki.yaml`     | Where the generated config is written.            |
| `GRAFANA_LOKI_DATA_DIR`             | `/opt/containers/data`                 | Chunks, rules and retention data.                 |
| `GRAFANA_LOKI_TARGET`               | *(unset = `all`)*                      | Component target: `read`, `write` or `backend`.   |
| `GRAFANA_LOKI_LISTEN_PORT_HTTP`     | `3100`                                 | HTTP API port.                                    |
| `GRAFANA_LOKI_LISTEN_PORT_GRPC`     | `9096`                                 | gRPC port.                                        |
| `GRAFANA_LOKI_STORAGE_BACKEND`      | `filesystem`                           | `filesystem` or `s3`.                             |
| `GRAFANA_LOKI_MEMBERLIST`           | *(unset)*                              | Comma-separated memberlist peers; enables clustering. |
| `GRAFANA_LOKI_INSTANCE_ADDRESS`     | *(unset)*                              | Advertised instance address.                      |
| `GRAFANA_LOKI_COMPACTOR_ADDRESS`    | *(unset)*                              | Compactor address for the cluster.                |
| `GRAFANA_LOKI_USER` / `_GROUP`      | `loki`                                 | Unprivileged account the process runs as.         |
| `GRAFANA_LOKI_READY_MAX_ATTEMPTS`   | `30`                                   | Readiness probe attempts.                         |
| `GRAFANA_LOKI_READY_ATTEMPT_INTERVAL`| `1`                                   | Seconds between probe attempts.                   |

The config is regenerated on every start from these variables — mount a file
at `GRAFANA_LOKI_CONFIG`'s path only if you want to inspect it, not to edit it,
because Loki is configured from the environment, not the file.

### S3 storage

Set `GRAFANA_LOKI_STORAGE_BACKEND=s3` and the object storage moves from the
local disk to S3 (or any S3-compatible store such as
[MinIO](https://github.com/supanadit/containers)):

| Variable                            | Default              |
| ----------------------------------- | -------------------- |
| `GRAFANA_LOKI_S3_BUCKET`            | `loki`               |
| `GRAFANA_LOKI_S3_ENDPOINT`          | `s3.amazonaws.com`   |
| `GRAFANA_LOKI_S3_ACCESS_KEY`        | `your-access-key`    |
| `GRAFANA_LOKI_S3_SECRET_KEY`        | `your-secret-key`    |
| `GRAFANA_LOKI_S3_REGION`            | `us-east-1`          |
| `GRAFANA_LOKI_S3_FORCE_PATH_STYLE`  | `true`               |
| `GRAFANA_LOKI_S3_INSECURE`          | `false`              |

```yaml
environment:
  GRAFANA_LOKI_STORAGE_BACKEND: s3
  GRAFANA_LOKI_S3_ENDPOINT: minio:9000
  GRAFANA_LOKI_S3_BUCKET: loki
  GRAFANA_LOKI_S3_ACCESS_KEY: minio
  GRAFANA_LOKI_S3_SECRET_KEY: minio123
  GRAFANA_LOKI_S3_INSECURE: "true"
```

## Clustering

Set `GRAFANA_LOKI_MEMBERLIST` to a comma-separated list of peer addresses and
the entrypoint renders a `memberlist:` block (port 7946, gossip) and switches
the ring KV store from `inmemory` to `memberlist`. Split roles across replicas
with `GRAFANA_LOKI_TARGET=read` / `write` / `backend`.

```yaml
environment:
  GRAFANA_LOKI_MEMBERLIST: loki-1:7946,loki-2:7946,loki-3:7946
```

> [!NOTE]
> The generated config uses the TSDB store with schema v13, a single
> replication factor by default, an embedded query-results cache and pattern
> ingestion — a sane single-binary setup that still scales out with memberlist.

## Health

The composite health endpoint runs on the shared health server (default port
`8080`): Loki must answer `/ready`, the process must be alive, the data
directory must have free space and there must be no zombie children.

```bash
curl -s http://localhost:8080/health
curl -s http://localhost:8080/loki/ready
```

## Next steps

- Visualize logs with [Grafana](/grafana).
- Collect metrics with [Prometheus](/prometheus).
- [Volumes](/volumes) explains what to persist.

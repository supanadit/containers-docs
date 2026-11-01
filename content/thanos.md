# Thanos

Thanos turns a set of Prometheus servers into a globally scalable, highly
available metrics system backed by object storage. Thanos ships seven
components in one binary, and this image runs any of them: pick one with
`THANOS_COMPONENT`, configure it with plain env vars, and the
[ezx](https://github.com/supanadit/ezx) bootstrap supervises it — including
rendering the object-store config to a `0600` file so credentials never appear
in the process list.

## Quickstart

A Thanos **query** frontend over one store:

```bash
docker run -d \
  --name thanos-query \
  -e THANOS_COMPONENT=query \
  -e THANOS_QUERY_STORE_ADDRESSES=thanos-sidecar:10901 \
  -p 9090:10902 \
  ghcr.io/supanadit/containers/thanos
```

## Choosing a component

`THANOS_COMPONENT` selects what runs (default `query`):

| Value             | Role                                                      |
| ----------------- | --------------------------------------------------------- |
| `query`           | PromQL federation across sidecars/stores/receivers.        |
| `query-frontend`  | Caching, splitting front end in front of query.            |
| `sidecar`         | Sits next to [Prometheus](/prometheus), ships blocks. |
| `store`           | Serves object-storage blocks as a store API.               |
| `receive`         | Accepts Prometheus remote write.                           |
| `rule`            | Runs recording and alerting rules.                         |
| `compact`         | Compacts and downsamples blocks in object storage.         |

All components listen on `THANOS_HTTP_ADDRESS` (default `0.0.0.0:10902`); the
gRPC-serving components (query, sidecar, store, receive, rule) also bind
`THANOS_GRPC_ADDRESS` (default `0.0.0.0:10901`). Run one component per
container and scale them independently.

## Configuration

Common:

| Variable                  | Default                 | Description                     |
| ------------------------- | ----------------------- | ------------------------------- |
| `THANOS_COMPONENT`        | `query`                 | The component to run.           |
| `THANOS_HTTP_ADDRESS`     | `0.0.0.0:10902`         | HTTP address.                   |
| `THANOS_GRPC_ADDRESS`     | `0.0.0.0:10901`         | gRPC address.                   |
| `LOG_LEVEL`               | `info`                  | Log verbosity.                  |

Per component (selected rows):

| Variable                                | Default                  | Applies to      |
| --------------------------------------- | ------------------------ | --------------- |
| `THANOS_QUERY_STORE_ADDRESSES`          | *(unset)*                | `query`         |
| `THANOS_QUERY_ENDPOINTS`                | *(unset)*                | `query`, `rule` |
| `THANOS_REPLICA_LABEL`                  | *(unset)*                | `query`         |
| `THANOS_QUERY_FRONTEND_DOWNSTREAM_URL`  | *(unset)*                | `query-frontend`|
| `THANOS_SIDECAR_PROMETHEUS_URL`         | `http://localhost:9090`  | `sidecar`       |
| `THANOS_SIDECAR_SHIPPER_UPLOAD_COMPACTED` | `false`                | `sidecar`       |
| `THANOS_RECEIVE_HASHRING_FILE`          | *(unset)*                | `receive`       |
| `THANOS_RECEIVE_REPLICATION_FACTOR`     | `1`                      | `receive`       |
| `THANOS_REMOTE_WRITE_ADDRESS`           | `0.0.0.0:10903`          | `receive`       |
| `THANOS_COMPACT_RETENTION_RAW` / `_5M` / `_1H` | *(unset)*         | `compact`       |
| `THANOS_COMPACT_DOWNSAMPLING_DISABLE`   | `false`                  | `compact`       |
| `THANOS_RULE_FILES`                     | *(unset)*                | `rule`          |
| `THANOS_ALERTMANAGERS_URL`              | *(unset)*                | `rule`          |

Comma-separated values (`THANOS_QUERY_STORE_ADDRESSES`,
`THANOS_RULE_FILES`, `THANOS_ALERTMANAGERS_URL`, `THANOS_QUERY_ENDPOINTS`)
are split into repeated flags. The `receive` component also picks up external
labels from `THANOS_RECEIVE_LABELS_<NAME>` variables.

## Object storage (S3)

The sidecar, store, receive, rule and compact components read an objstore
config. The simplest path is the S3 shorthand — set all four and the
entrypoint renders a `0600` config file at
`/opt/containers/config/thanos-objstore.yaml`:

| Variable                   | Default   | Description                          |
| -------------------------- | --------- | ------------------------------------ |
| `THANOS_S3_BUCKET`         | *(unset)* | Bucket name.                         |
| `THANOS_S3_ENDPOINT`       | *(unset)* | S3 endpoint (e.g. `minio:9000`).     |
| `THANOS_S3_ACCESS_KEY`     | *(unset)* | Access key.                          |
| `THANOS_S3_SECRET_KEY`     | *(unset)* | Secret key.                          |
| `THANOS_S3_INSECURE`       | `false`   | Use plain HTTP (MinIO on plain HTTP).|
| `THANOS_S3_SIGNATURE_V2`   | `false`   | V2 signatures.                       |
| `THANOS_S3_CA_FILE`        | *(unset)* | Custom CA bundle.                    |

```yaml
environment:
  THANOS_COMPONENT: sidecar
  THANOS_SIDECAR_PROMETHEUS_URL: http://prometheus:9090
  THANOS_S3_BUCKET: thanos
  THANOS_S3_ENDPOINT: minio:9000
  THANOS_S3_ACCESS_KEY: minio
  THANOS_S3_SECRET_KEY: minio123
  THANOS_S3_INSECURE: "true"
```

For non-S3 stores, pass a full config inline via `THANOS_OBJSTORE_CONFIG` or
point `THANOS_OBJSTORE_CONFIG_FILE` at a mounted file — both win over the
shorthand.

> [!NOTE]
> Because the S3 credentials land in a `0600` file read with
> `--objstore.config-file`, they never appear in `docker inspect` or `ps`.

## A minimal end-to-end stack

```yaml
services:
  prometheus:
    image: ghcr.io/supanadit/containers/prometheus
    environment:
      PROMETHEUS_ENABLE_WEB_LIFECYCLE: "true"

  sidecar:
    image: ghcr.io/supanadit/containers/thanos
    environment:
      THANOS_COMPONENT: sidecar
      THANOS_SIDECAR_PROMETHEUS_URL: http://prometheus:9090
      THANOS_S3_ENDPOINT: minio:9000
      THANOS_S3_BUCKET: thanos
      THANOS_S3_ACCESS_KEY: minio
      THANOS_S3_SECRET_KEY: minio123
      THANOS_S3_INSECURE: "true"
    volumes:
      - prom-data:/opt/containers/data   # sidecar reads Prometheus' TSDB

  query:
    image: ghcr.io/supanadit/containers/thanos
    environment:
      THANOS_COMPONENT: query
      THANOS_QUERY_STORE_ADDRESSES: sidecar:10901

volumes:
  prom-data:
```

## Health

The composite health endpoint runs on the shared health server (default port
`8080`). The selected component must answer `/-/ready` on its HTTP address;
the composite also checks the process, free disk and zombie reaping.

```bash
curl -s http://localhost:10902/-/ready
curl -s http://localhost:8080/health
```

## Next steps

- [Prometheus](/prometheus) — the scraper the sidecar partners with.
- [Mimir](/mimir) — the push-based alternative for long-term storage.
- [Grafana](/grafana) — query Thanos as a Prometheus datasource.

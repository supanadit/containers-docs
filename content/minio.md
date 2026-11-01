# MinIO

S3-compatible object storage, built from source because MinIO no longer
publishes community-edition binaries — this image compiles the community
edition and supervises it with [ezx](https://github.com/supanadit/ezx). The
S3 API, the web console, and the shared composite health endpoint are all
exposed; everything is configured through environment variables.

## Quickstart

```bash
docker run -d \
  --name minio \
  -e MINIO_ROOT_USER=superadmin \
  -e MINIO_ROOT_PASSWORD=supersecretpassword \
  -v minio-data:/opt/containers/data \
  -p 9000:9000 \
  -p 9001:9001 \
  ghcr.io/supanadit/containers/minio
```

Point any S3 client at `http://localhost:9000` and the console at
`http://localhost:9001`.

> [!TIP]
> Prefer the file-based variants `MINIO_ROOT_USER_FILE` and
> `MINIO_ROOT_PASSWORD_FILE`: an inline `MINIO_ROOT_PASSWORD` is visible to
> every process in the container (and in `docker inspect`). When both are set,
> the `*_FILE` variables win.

## Configuration

| Variable | Default | Description |
| --- | --- | --- |
| `MINIO_ADDRESS` | `:9000` | S3 API listen address. |
| `MINIO_CONSOLE_ADDRESS` | `:9001` | Web console listen address. |
| `MINIO_ROOT_USER` | *(empty)* | Root access key (required, inline). |
| `MINIO_ROOT_PASSWORD` | *(empty)* | Root secret key (required, inline). |
| `MINIO_ROOT_USER_FILE` | *(empty)* | File containing the root access key — preferred over the inline variable. |
| `MINIO_ROOT_PASSWORD_FILE` | *(empty)* | File containing the root secret key. |
| `MINIO_BROWSER_REDIRECT_URL` | *(derived)* | Console redirect URL; derived from the container IP standalone, must be set explicitly in distributed mode. |
| `MINIO_DISTRIBUTED_MODE_ENABLED` | `false` | Enables distributed mode. |
| `MINIO_DISTRIBUTED_NODES` | *(empty)* | Drive expansion string, e.g. `http://minio{1...4}/data{1...4}`. |
| `MINIO_EXTRA_DRIVES` | *(empty)* | Extra drive paths for single-node multi-drive, e.g. `/opt/containers/data/disk{1...4}`. |
| `MINIO_EXTRA_ARGS` | *(empty)* | Comma-separated extra `server` flags, e.g. `--certs-dir,/opt/containers/config/certs`. |
| `MINIO_DATA_DIR` | `/opt/containers/data` | Holds one or more drive directories. |
| `LOG_LEVEL` | `info` | Container-wide (ezx) log level. |
| `MINIO_LOG_LEVEL` | *(empty)* | MinIO's own log level, kept distinct from `LOG_LEVEL`. |
| `SLEEP_MODE` | `false` | Start the supervisor but not the server (debugging). |

## Health

The composite endpoint proxies MinIO's own readiness and liveness probes
(`/minio/health/ready`, `/minio/health/live`) plus a TCP check on the console
port, and the ezx API serves it on port 8080:

```bash
curl -s http://localhost:8080/health
```

See [Health & readiness](/docs/health) for the endpoint contract.

## TLS

MinIO enables TLS when it finds certificates in its certs directory. Point the
server at your mounted certificates with the escape hatch:

```bash
docker run -d \
  --name minio \
  -e MINIO_ROOT_USER_FILE=/run/secrets/minio-user \
  -e MINIO_ROOT_PASSWORD_FILE=/run/secrets/minio-password \
  -e MINIO_EXTRA_ARGS=--certs-dir,/opt/containers/config/certs \
  -v minio-certs:/opt/containers/config/certs:ro \
  -v minio-data:/opt/containers/data \
  -p 9000:9000 -p 9001:9001 \
  ghcr.io/supanadit/containers/minio
```

`MINIO_EXTRA_ARGS` (comma-separated) is the only supported way to add
MinIO server flags — ezx owns the argv, so there is no shell to append to.

## Distributed mode

For erasure-coded, multi-node storage enable distributed mode and describe the
pool with a drive expansion string:

```yaml
services:
  minio-1:
    image: ghcr.io/supanadit/containers/minio
    environment:
      MINIO_ROOT_USER: superadmin
      MINIO_ROOT_PASSWORD: supersecretpassword
      MINIO_DISTRIBUTED_MODE_ENABLED: "true"
      MINIO_DISTRIBUTED_NODES: "http://minio{1...4}/opt/containers/data"
    volumes: [minio-1-data:/opt/containers/data]
```

Run the same configuration on every node (`minio-1` … `minio-4`); each node
discovers the others through the expansion string. Set
`MINIO_BROWSER_REDIRECT_URL` explicitly to a load-balanced console address so
all nodes redirect to the same console.

> [!NOTE]
> Use at least 4 drives per erasure set for tolerable fault tolerance, and pin
> the same `MINIO_ROOT_USER`/`MINIO_ROOT_PASSWORD` across all nodes — they are
> the cluster-wide credentials.

## Next steps

- [Configuration](/docs/configuration) — how env vars become arguments.
- [Volumes](/docs/volumes) — the `/opt/containers/*` layout.

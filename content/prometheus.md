# Prometheus

Prometheus is the metrics collector and TSDB at the heart of most monitoring
stacks. This image wraps the upstream binary in the same bootstrap as every
container here: you configure it with `PROMETHEUS_*` environment variables, the
entrypoint seeds a config file if you have not mounted one, and
[ezx](https://github.com/supanadit/ezx) supervises the process, rotates the
logs and probes readiness.

## Quickstart

```bash
docker run -d \
  --name prometheus \
  -v prometheus-data:/opt/containers/data \
  -v prometheus-config:/opt/containers/config \
  -p 9090:9090 \
  ghcr.io/supanadit/containers/prometheus
```

With no mounted config, the image seeds the bundled default `prometheus.yml`
into `/opt/containers/config` so the container starts out of the box. To bring
your own scrape configuration, mount a file at
`/opt/containers/config/prometheus.yml`.

```yaml
services:
  prometheus:
    image: ghcr.io/supanadit/containers/prometheus
    volumes:
      - prometheus-data:/opt/containers/data
      - ./prometheus.yml:/opt/containers/config/prometheus.yml:ro
    ports:
      - "9090:9090"

volumes:
  prometheus-data:
```

## Configuration

| Variable                                  | Default                                | Description                                            |
| ----------------------------------------- | -------------------------------------- | ------------------------------------------------------ |
| `PROMETHEUS_PORT`                         | `9090`                                 | Web UI and API listen port.                            |
| `PROMETHEUS_CONFIG_FILE`                  | `/opt/containers/config/prometheus.yml`| Path to the scrape config.                             |
| `PROMETHEUS_DATA_DIR`                     | `/opt/containers/data`                 | TSDB data directory.                                   |
| `PROMETHEUS_CONFIG_DIR`                   | `/opt/containers/config`               | Configuration directory.                               |
| `PROMETHEUS_STORAGE_TSDB_RETENTION_TIME`  | *(unset)*                              | TSDB time-based retention (e.g. `15d`).                |
| `PROMETHEUS_STORAGE_TSDB_RETENTION_SIZE`  | *(unset)*                              | TSDB size-based retention (e.g. `10GB`).               |
| `PROMETHEUS_STORAGE_TSDB_MIN_BLOCK_DURATION` | *(unset)*                           | Minimum TSDB block duration.                           |
| `PROMETHEUS_STORAGE_TSDB_MAX_BLOCK_DURATION` | *(unset)*                           | Maximum TSDB block duration.                           |
| `PROMETHEUS_WEB_CONFIG_FILE`              | *(unset)*                              | TLS/basic-auth web config file (`--web.config.file`).  |
| `LOG_LEVEL`                               | `info`                                 | Log verbosity.                                         |

Retention and block-duration flags are only added to the command line when
their variable is set — unset never becomes an empty `--flag=`.

### Feature toggles

Web endpoints and `--enable-feature` gates are plain booleans
(`true`/`false`/`1`/`0`/`yes`/`no`):

| Variable                                   | Enables                             |
| ------------------------------------------ | ----------------------------------- |
| `PROMETHEUS_ENABLE_WEB_LIFECYCLE`          | `/-/reload` and `/-/quit` endpoints |
| `PROMETHEUS_ENABLE_WEB_REMOTE_WRITE_RECEIVER` | Remote-write receiver API        |
| `PROMETHEUS_ENABLE_WEB_OTLP_RECEIVER`      | OTLP ingestion receiver             |
| `PROMETHEUS_ENABLE_NATIVE_HISTOGRAM`       | `native-histograms`                 |
| `PROMETHEUS_ENABLE_EXEMPLAR_STORAGE`       | `exemplar-storage`                  |
| `PROMETHEUS_ENABLE_PROMQL_FUNCTIONS`       | `promql-experimental-functions`     |
| `PROMETHEUS_ENABLE_AUTO_RELOAD_CONFIG`     | `auto-reload-config`                |

That is a selection — the full set of `PROMETHEUS_ENABLE_*` flags lives in
`entrypoint.d/bootstrap/env.js` in the [repository](https://github.com/supanadit/containers).

### Readiness tuning

| Variable                            | Default | Description                       |
| ----------------------------------- | ------- | --------------------------------- |
| `PROMETHEUS_READY_MAX_ATTEMPTS`     | `30`    | Readiness probe attempts.         |
| `PROMETHEUS_READY_ATTEMPT_INTERVAL` | `1`     | Seconds between probe attempts.   |

## Custom scrape configuration

The config file is the only thing Prometheus needs beyond env vars. When the
path is the container default and nothing is mounted, the entrypoint copies the
bundled default config into place; a custom path you set with
`PROMETHEUS_CONFIG_FILE` is never fabricated — if it does not exist, the
container fails fast with a validation error instead of guessing.

> [!TIP]
> Enable `PROMETHEUS_ENABLE_WEB_LIFECYCLE=true` and reload after editing a
> mounted config with `curl -X POST http://localhost:9090/-/reload` — no
> restart needed.

## Health

The composite health endpoint runs on the shared health server (default port
`8080`). It reports healthy when Prometheus answers `/-/ready`, the supervised
process is alive, the data directory has free space and there are no zombie
children.

```bash
curl -s http://localhost:8080/health
curl -s http://localhost:8080/health/comprehensive | jq
```

## Next steps

- Ship metrics long-term with [Mimir](/mimir) or [Thanos](/thanos).
- Visualize them with [Grafana](/grafana).
- [Configuration](/configuration) explains the env-var conventions.

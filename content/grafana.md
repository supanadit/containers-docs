# Grafana

Grafana is the dashboard and visualization layer for your metrics and logs.
This image keeps Grafana's own `GF_*` variables working, normalizes the data,
logs and provisioning paths onto the standard `/opt/containers/*` volumes, and
lets the [ezx](https://github.com/supanadit/ezx) bootstrap supervise the
server, rotate logs and probe `/api/health`.

## Quickstart

```bash
docker run -d \
  --name grafana \
  -v grafana-data:/opt/containers/data \
  -p 3000:3000 \
  ghcr.io/supanadit/containers/grafana
```

Log in at `http://localhost:3000`. The container seeds a default `grafana.ini`
into `/opt/containers/config` when you have not mounted one, so a bare
`docker run` works.

```yaml
services:
  grafana:
    image: ghcr.io/supanadit/containers/grafana
    environment:
      GF_SERVER_HTTP_PORT: "3000"
      GF_SECURITY_ADMIN_PASSWORD: secret
    volumes:
      - grafana-data:/opt/containers/data
    ports:
      - "3000:3000"

volumes:
  grafana-data:
```

Because `GF_*` variables pass straight through to Grafana, anything Grafana
documents natively — including `GF_SECURITY_ADMIN_PASSWORD` — works as-is.

## Configuration

| Variable                        | Default                                  | Description                                        |
| ------------------------------- | ---------------------------------------- | -------------------------------------------------- |
| `GRAFANA_PORT`                  | `3000` (or `GF_SERVER_HTTP_PORT`)        | HTTP port; also used by the readiness probe.       |
| `GF_PATHS_DATA`                 | `/opt/containers/data`                   | Grafana database and data.                         |
| `GF_PATHS_LOGS`                 | `/opt/containers/logs`                   | Log directory.                                     |
| `GF_PATHS_CONFIG`               | `/opt/containers/config/grafana.ini`     | Main config file.                                  |
| `GF_PATHS_PROVISIONING`         | `/opt/containers/config/provisioning`    | Provisioning directory (dashboards, datasources).  |
| `GF_PATHS_PLUGINS`              | `/var/lib/grafana/plugins`               | Plugin directory.                                  |
| `GF_PATHS_HOME`                 | `/usr/share/grafana`                     | Grafana home.                                      |
| `GRAFANA_CONFIG_DIR`            | `/opt/containers/config`                 | Configuration volume.                              |
| `GRAFANA_USER` / `GRAFANA_GROUP`| `grafana`                                | The unprivileged account the server runs as.       |
| `GRAFANA_PACKAGING`             | `docker`                                 | Passed as `--packaging`.                           |
| `GRAFANA_READY_MAX_ATTEMPTS`    | `30`                                     | Readiness probe attempts.                          |
| `GRAFANA_READY_ATTEMPT_INTERVAL`| `1`                                      | Seconds between probe attempts.                    |

As with every container here, `env.get` treats unset and empty the same, and
booleans accept `true`/`false`/`1`/`0`/`yes`/`no`.

## Provisioning datasources

Grafana reads datasources, dashboards and alerting resources from the
provisioning directory. Mount your files under
`/opt/containers/config/provisioning`:

```yaml
volumes:
  - ./provisioning:/opt/containers/config/provisioning:ro
```

> [!TIP]
> Point a datasource at [Prometheus](/prometheus),
> [Loki](/loki) or [Mimir](/mimir) running beside it on the same
> compose network — e.g. `http://prometheus:9090` — and Grafana picks it up on
> boot, no UI clicking required.

## Custom configuration

If you mount your own `grafana.ini` at the default path it is used unchanged.
If you repoint `GF_PATHS_CONFIG` to a custom path, that file must exist — the
container never invents a config at a path you chose, it fails fast instead.

## Health

The composite health endpoint runs on the shared health server (default port
`8080`): Grafana must answer `/api/health`, the supervised process must be
alive, the data directory must have free space and there must be no zombie
children.

```bash
curl -s http://localhost:8080/health
curl -s http://localhost:8080/grafana/health
```

## Next steps

- [Configuration](/configuration) explains the env-var conventions.
- Collect logs with [Loki](/loki) and metrics with
  [Prometheus](/prometheus).

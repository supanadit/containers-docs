# Configuration

All configuration happens through environment variables. There is no config
file to mount, no init script to run: at boot, each container validates its
environment, generates the application's native config file, and starts the
app.

## How it works

Each container ships a small JavaScript module set
(`entrypoint.d/bootstrap/`) that runs under
[ezx](https://github.com/supanadit/ezx):

1. **`env.js`** declares every environment variable with a type
   (`env.bool`, `env.int`) and a default — the single source of truth.
2. **`validation.js`** fails fast on contradictory or missing settings,
   *before* any side effect happens.
3. **`config.js`** renders the application config from typed values —
   never `sed`-edited templates.

Rules worth knowing:

- `env.get(name, default)` applies the default when the variable is set to
  the empty string, too. Unset and empty behave the same.
- Booleans accept `true`/`false`/`1`/`0`/`yes`/`no`.
- An empty `value` is still an argument: optional CLI flags are only added
  when their variable is set, so you never end up with `--flag=`.

## Naming convention

Variables are prefixed with the application name:

| Container  | Prefix       |
| ---------- | ------------ |
| PostgreSQL | `POSTGRES_`  |
| MariaDB    | `MARIADB_`   |
| Kafka      | `KAFKA_`     |
| etcd       | `ETCD_`      |
| Prometheus | `PROMETHEUS_`|
| Grafana    | `GRAFANA_`   |
| WordPress  | `WORDPRESS_` |

See each container's page for its full variable table.

## Secrets

Secret-bearing variables (passwords, keys) are consumed at spawn time: the
entrypoint strips them from the child process environment after generating
config, so a `ps eww` inside the container does not reveal them.

## Overrides

For values that are not exposed as variables, most containers read optional
drop-in configuration from `/opt/containers/config`. Mount a file there and
the entrypoint merges or references it instead of failing. Per-container
pages show the exact mechanism — for example
[PostgreSQL custom config](/docs/postgresql#custom-configuration).

# Volumes & backups

## Volume layout

Every container pre-creates the same tree under `/opt/containers` and chowns
it to the application's dedicated user:

| Path                     | Purpose                         |
| ------------------------ | ------------------------------- |
| `/opt/containers/data`   | Application data — mount here   |
| `/opt/containers/config` | Generated + drop-in config      |
| `/opt/containers/logs`   | Logs, rotated by ezx            |
| `/opt/containers/run`    | Sockets, PIDs, runtime files    |
| `/opt/containers/backup` | Backup output                   |

Mount a named volume or bind mount at `data` (and `backup` if you use
backups). Do not mount over the whole `/opt/containers` tree: the entrypoint
needs to write generated config into `config` at boot.

> [!WARNING]
> Chowning is best-effort by design. If you bind-mount a host directory that
> is read-only or has a foreign owner, the container still starts — check the
> logs for chown warnings instead of assuming a hard failure.

## Built-in backups

Several containers ship scheduled backup jobs (PostgreSQL with pgBackRest,
MariaDB with scheduled dumps). Backups are ordinary ezx scheduler nodes: they
run inside the same container, drop output into `/opt/containers/backup`, and
never shell out.

Typical setup:

```yaml
volumes:
  - pgdata:/opt/containers/data
  - pgbackup:/opt/containers/backup
environment:
  PGBACKREST_ENABLED: "true"
```

Per-container pages (see [PostgreSQL](/docs/postgresql#backups)) list the
exact variables: schedule, retention, and repository configuration.

## Restoring

Backups are plain upstream formats — `pgbackrest restore`, `mariadb-dump`
output — so any standard restore procedure works. There is no proprietary
format to reverse.

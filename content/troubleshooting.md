# Troubleshooting

## The container exits immediately

Validation runs before any side effect. If `validation.js` rejects the
environment — a missing password, contradictory replication settings — the
container exits on purpose. Read the first log lines:

```bash
docker logs <container>
```

The error names the exact variable that failed.

## The app is up but `/health` is not healthy

The composite endpoint only flips to healthy when the application answers on
its own protocol. Common causes:

- Slow first boot (e.g. initializing a data directory). Give the health check
  more `retries`/`interval` before assuming failure.
- A config value mismatch: the probe speaks the app's protocol, so a bad
  port or credentials in the environment show up here.
- If `EZX_HEALTH_ADDR` is unset, the health server never started — set it and
  check the startup warning.

## Permission errors on mounted volumes

Volumes are chowned best-effort. A `:ro` or foreign-owner bind mount logs a
warning instead of aborting the container, but the app inside may not be able
to write. Fix the mount owner, or mount at a path the entrypoint does not
recurse into. See [Volumes & backups](/volumes).

## Checking what the entrypoint decided

The entrypoint writes the generated config under `/opt/containers/config`
before starting the app. Diff what is on disk against what you intended:

```bash
docker exec -it <container> ls -R /opt/containers/config
docker exec -it <container> cat /opt/containers/config/<app-config-file>
```

Each container page names its config file (for example `postgresql.conf`,
`server.properties`, `prometheus.yml`).

## Logs rotate away too fast

Log rotation is handled by ezx in `/opt/containers/logs`. If you need raw
output for a crash, `docker logs` still carries stdout — configure your
orchestrator to persist it.

## Still stuck

Open a [GitHub issue](https://github.com/supanadit/containers/issues) with:

1. The `docker logs` output from boot.
2. The compose file or `docker run` command (redact secrets).
3. The image tag — `<container>-rX.Y.Z` format.

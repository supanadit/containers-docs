# Quickstart

## Pull an image

Every image is published to GHCR:

```bash
docker pull ghcr.io/supanadit/containers/postgresql
```

Tags follow `<container>-rX.Y.Z` (for example
`ghcr.io/supanadit/containers/postgresql:postgresql-r1.2.3`) so you can pin
exactly the version you tested.

## Run one

The smallest useful PostgreSQL:

```bash
docker run -d \
  --name postgres \
  -e POSTGRES_DB=app \
  -e POSTGRES_PASSWORD=secret \
  -v postgres-data:/opt/containers/data \
  -p 5432:5432 \
  ghcr.io/supanadit/containers/postgresql
```

Connect from another container and check readiness on the health endpoint:

```bash
docker exec -it postgres psql -U app -c 'select version();'
curl -s http://localhost:5432/health
```

> [!TIP]
> Every container exposes a composite health endpoint. Orchestrators should
> probe it instead of a raw TCP handshake — see [Health & readiness](/health).

## Run with Compose

```yaml
services:
  postgres:
    image: ghcr.io/supanadit/containers/postgresql
    environment:
      POSTGRES_DB: app
      POSTGRES_PASSWORD: secret
    volumes:
      - postgres-data:/opt/containers/data
    ports:
      - "5432:5432"

volumes:
  postgres-data:
```

## Where things live

Inside every container, the application tree is laid out the same way:

| Path                      | Purpose                    |
| ------------------------- | -------------------------- |
| `/opt/containers/data`    | Application data           |
| `/opt/containers/config`  | Generated configuration    |
| `/opt/containers/logs`    | Application logs (rotated) |
| `/opt/containers/run`     | Runtime files, sockets     |
| `/opt/containers/backup`  | Backup output              |

Mount volumes at these paths and nothing else — the directories are
pre-created and owned by the application's dedicated user.

## Next steps

- [Configuration](/configuration) — how env vars become config files.
- Pick a container from the sidebar for its specific variables and examples.

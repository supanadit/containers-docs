---
sidebar_position: 3
---

# PgBouncer

This container image includes built-in support for PgBouncer, a lightweight connection pooler for PostgreSQL. To enable PgBouncer in your PostgreSQL container, set the `PGBOUNCER_ENABLE` environment variable to `true` in your Docker Compose configuration as shown below:

```yaml
services:
  postgresql:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    ports:
      - "5434:5432"
      - "6432:6432" # PgBouncer port
    environment:
      POSTGRES_PASSWORD: secret
      // highlight-next-line
      PGBOUNCER_ENABLE: "true"
    volumes:
      - ./.data:/usr/local/pgsql/data
```

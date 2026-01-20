---
title: Citus
sidebar_position: 5
---

:::danger
The Citus extension is supported starting with PostgreSQL version 13 and above, but it is not compatible with PostgreSQL 18. The Citus team is still working on PostgreSQL 18 compatibility.
:::

# Citus

This PostgreSQL container image includes built-in support for the Citus extension, which transforms PostgreSQL into a distributed database capable of handling large-scale data and high-throughput workloads. Citus allows you to distribute your data across multiple nodes, enabling horizontal scaling and improved performance for complex queries.

```yaml
networks:
  default:
    name: container-example-network
    external: true

services:
  postgresql-coordinator:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    container_name: postgresql-coordinator
    ports:
      - "5432:5432"
    environment:
      CITUS_ENABLE: "true"
      CITUS_ROLE: coordinator
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_LISTEN_ADDRESSES: "*"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
      PGBACKREST_ENABLE: "true"
    volumes:
      - postgresql_coordinator_data:/usr/local/pgsql/data
      - postgresql_coordinator_backup:/usr/local/pgsql/backup
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -h localhost -p 5432 -U postgres"]
      interval: 30s
      timeout: 10s
      retries: 3

  postgresql-worker1:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    container_name: postgresql-worker1
    depends_on:
      postgresql-coordinator:
        condition: service_healthy
    ports:
      - "5433:5432"
    environment:
      CITUS_ENABLE: "true"
      CITUS_ROLE: worker
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_LISTEN_ADDRESSES: "*"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
      PGBACKREST_ENABLE: "true"
    volumes:
      - postgresql_worker1_data:/usr/local/pgsql/data
      - postgresql_worker1_backup:/usr/local/pgsql/backup
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -h localhost -p 5432 -U postgres"]
      interval: 30s
      timeout: 10s
      retries: 3

  postgresql-worker2:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    container_name: postgresql-worker2
    depends_on:
      postgresql-coordinator:
        condition: service_healthy
    ports:
      - "5434:5432"
    environment:
      CITUS_ENABLE: "true"
      CITUS_ROLE: worker
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_LISTEN_ADDRESSES: "*"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
      PGBACKREST_ENABLE: "true"
    volumes:
      - postgresql_worker2_data:/usr/local/pgsql/data
      - postgresql_worker2_backup:/usr/local/pgsql/backup
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -h localhost -p 5432 -U postgres"]
      interval: 30s
      timeout: 10s
      retries: 3

volumes:
  postgresql_coordinator_data:
  postgresql_coordinator_backup:
  postgresql_worker1_data:
  postgresql_worker1_backup:
  postgresql_worker2_data:
  postgresql_worker2_backup:
```

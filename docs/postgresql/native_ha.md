---
id: native_ha
slug: /postgresql/native-ha
title: Native HA (Deprecated)
---

# Native HA Documentation Moved

The Native HA documentation has been reorganized. Please use the new structure:

## New Documentation Location

### [Native HA Introduction](/docs/postgresql/native_ha/intro)

Overview of native streaming replication, comparison with Patroni.

### [Synchronization Modes](/docs/postgresql/native_ha/sync_modes)

Single sync, quorum sync, and async replication modes.

### [Replica Names](/docs/postgresql/native_ha/replica_names)

Application names and naming conventions.

### [Dynamic Configuration](/docs/postgresql/native_ha/dynamic_config)

Runtime configuration changes with `pg-reload-sync-config.sh`.

### [Troubleshooting](/docs/postgresql/native_ha/troubleshooting)

Common issues and solutions.

---

## Quick Comparison

| Feature | Native HA | Patroni |
|---------|-----------|---------|
| External dependencies | None | etcd/Consul/ZooKeeper |
| Automatic failover | No | Yes |
| Quorum sync support | Yes | Via DCS |
| Dynamic configuration | Yes | Via DCS |

To set up a native high-availability PostgreSQL cluster using streaming replication, you can use the following Docker Compose configuration. This setup includes one primary PostgreSQL instance and two replica instances that replicate data from the primary.

```yaml
services:
  postgresql-primary:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    container_name: postgresql-primary
    ports:
      - "5432:5432"
    environment:
      HA_MODE: native
      REPLICATION_ROLE: primary
      REPLICATION_USER: replicator
      REPLICATION_PASSWORD: replicator_password
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_LISTEN_ADDRESSES: "*"
      PGBACKREST_ENABLE: "true"
    volumes:
      - postgresql_primary_data:/opt/containers/data
      - pgbackrest_primary_data:/opt/containers/backup
    networks:
      - postgres-cluster
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -h localhost -p 5432 -U postgres"]
      interval: 30s
      timeout: 10s
      retries: 3

  postgresql-replica1:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    container_name: postgresql-replica1
    depends_on:
      postgresql-primary:
        condition: service_healthy
    ports:
      - "5433:5432"
    environment:
      HA_MODE: native
      REPLICATION_ROLE: replica
      PRIMARY_HOST: postgresql-primary
      PRIMARY_PORT: 5432
      REPLICATION_USER: replicator
      REPLICATION_PASSWORD: replicator_password
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_LISTEN_ADDRESSES: "*"
    volumes:
      - postgresql_replica1_data:/opt/containers/data
    networks:
      - postgres-cluster
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -h localhost -p 5432 -U postgres"]
      interval: 30s
      timeout: 10s
      retries: 3

  postgresql-replica2:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    container_name: postgresql-replica2
    depends_on:
      postgresql-primary:
        condition: service_healthy
    ports:
      - "5434:5432"
    environment:
      HA_MODE: native
      REPLICATION_ROLE: replica
      PRIMARY_HOST: postgresql-primary
      PRIMARY_PORT: 5432
      REPLICATION_USER: replicator
      REPLICATION_PASSWORD: replicator_password
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_LISTEN_ADDRESSES: "*"
    volumes:
      - postgresql_replica2_data:/opt/containers/data
    networks:
      - postgres-cluster
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -h localhost -p 5432 -U postgres"]
      interval: 30s
      timeout: 10s
      retries: 3

volumes:
  postgresql_primary_data:
  pgbackrest_primary_data:
  postgresql_replica1_data:
  postgresql_replica2_data:

networks:
  postgres-cluster:
    driver: bridge
```

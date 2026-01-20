---
sidebar_position: 2
---

# Patroni

To set up a high-availability PostgreSQL cluster using Patroni, you can use the following Docker Compose configuration. This setup includes three PostgreSQL instances managed by Patroni and an etcd instance for distributed configuration storage.

```yaml
services:
  etcd:
    image: supanadit/etcd:3.5
    container_name: etcd
    environment:
      ETCD_NAME: etcd0
      ETCD_ADVERTISE_CLIENT_URLS: http://etcd:2379
      ETCD_INITIAL_CLUSTER: etcd0=http://etcd:2380
      ETCD_INITIAL_CLUSTER_STATE: new
      ETCD_INITIAL_CLUSTER_TOKEN: postgres-cluster
    ports:
      - "2379:2379"
      - "2380:2380"
    volumes:
      - etcd_data:/var/lib/etcd
    networks:
      - postgres-cluster
    healthcheck:
      test:
        ["CMD", "etcdctl", "--endpoints=127.0.0.1:2379", "endpoint", "health"]
      interval: 5s
      timeout: 3s
      retries: 30

  postgresql1:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    container_name: postgresql1
    depends_on:
      etcd:
        condition: service_healthy
    ports:
      - "5432:5432"
      - "8008:8008"
    environment:
      PATRONI_ENABLE: "true"
      PATRONI_SCOPE: postgres-cluster
      PATRONI_NAME: postgresql1
      PATRONI_REST_HOST: 0.0.0.0
      PATRONI_REST_PORT: 8008
      ETCD_HOST: etcd
      ETCD_PORT: 2379
      POSTGRESQL_LISTEN_HOST: 0.0.0.0
      POSTGRESQL_PORT: 5432
      POSTGRESQL_CONNECT_HOST: postgresql1
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres_password
      PATRONI_REPLICATION_USER: replicator
      PATRONI_REPLICATION_PASSWORD: replicator_password
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      PGBACKREST_ENABLE: "true"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
    volumes:
      - postgresql1_data:/usr/local/pgsql/data
      - pgbackrest_data:/usr/local/pgsql/backup
    networks:
      - postgres-cluster
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -h localhost -p 5432 -U postgres"]
      interval: 30s
      timeout: 10s
      retries: 3

  postgresql2:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    container_name: postgresql2
    depends_on:
      etcd:
        condition: service_healthy
    ports:
      - "5433:5432"
      - "8009:8008"
    environment:
      PATRONI_ENABLE: "true"
      PATRONI_SCOPE: postgres-cluster
      PATRONI_NAME: postgresql2
      PATRONI_REST_HOST: 0.0.0.0
      PATRONI_REST_PORT: 8008
      ETCD_HOST: etcd
      ETCD_PORT: 2379
      POSTGRESQL_LISTEN_HOST: 0.0.0.0
      POSTGRESQL_PORT: 5432
      POSTGRESQL_CONNECT_HOST: postgresql2
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres_password
      PATRONI_REPLICATION_USER: replicator
      PATRONI_REPLICATION_PASSWORD: replicator_password
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      PGBACKREST_ENABLE: "true"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
    volumes:
      - postgresql2_data:/usr/local/pgsql/data
      - pgbackrest_data:/usr/local/pgsql/backup
    networks:
      - postgres-cluster
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -h localhost -p 5432 -U postgres"]
      interval: 30s
      timeout: 10s
      retries: 3

  postgresql3:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    container_name: postgresql3
    depends_on:
      etcd:
        condition: service_healthy
    ports:
      - "5434:5432"
      - "8010:8008"
    environment:
      PATRONI_ENABLE: "true"
      PATRONI_SCOPE: postgres-cluster
      PATRONI_NAME: postgresql3
      PATRONI_REST_HOST: 0.0.0.0
      PATRONI_REST_PORT: 8008
      ETCD_HOST: etcd
      ETCD_PORT: 2379
      POSTGRESQL_LISTEN_HOST: 0.0.0.0
      POSTGRESQL_PORT: 5432
      POSTGRESQL_CONNECT_HOST: postgresql3
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres_password
      PATRONI_REPLICATION_USER: replicator
      PATRONI_REPLICATION_PASSWORD: replicator_password
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      PGBACKREST_ENABLE: "true"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
    volumes:
      - postgresql3_data:/usr/local/pgsql/data
      - pgbackrest_data:/usr/local/pgsql/backup
    networks:
      - postgres-cluster
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -h localhost -p 5432 -U postgres"]
      interval: 30s
      timeout: 10s
      retries: 3

volumes:
  etcd_data:
  postgresql1_data:
  postgresql2_data:
  postgresql3_data:
  pgbackrest_data:

networks:
  postgres-cluster:
    driver: bridge
```

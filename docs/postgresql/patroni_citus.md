---
sidebar_position: 6
---

# Patroni + Citus

This PostgreSQL container image includes built-in support for both Patroni and the Citus extension. This allows you to set up a high-availability distributed PostgreSQL cluster that can handle large-scale data and high-throughput workloads. The following Docker Compose configuration sets up an etcd instance for distributed configuration storage, along with multiple PostgreSQL instances configured as Citus coordinators and workers, all managed by Patroni for high availability.

```yaml
networks:
  default:
    name: container-example-network
    external: true

services:
  etcd:
    image: quay.io/coreos/etcd:v3.5.13
    container_name: etcd
    environment:
      ETCDCTL_API: "3"
    command:
      - /usr/local/bin/etcd
      - --name=etcd0
      - --data-dir=/etcd-data
      - --initial-advertise-peer-urls=http://etcd:2380
      - --listen-peer-urls=http://0.0.0.0:2380
      - --advertise-client-urls=http://etcd:2379
      - --listen-client-urls=http://0.0.0.0:2379
      - --initial-cluster=etcd0=http://etcd:2380
      - --initial-cluster-state=new
      - --initial-cluster-token=citus-etcd
    ports:
      - "2379:2379"
    volumes:
      - etcd_data:/etcd-data
    healthcheck:
      test:
        ["CMD", "etcdctl", "--endpoints=127.0.0.1:2379", "endpoint", "health"]
      interval: 5s
      timeout: 3s
      retries: 30

  postgresql_c_1:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    pull_policy: never
    container_name: postgresql_c_1
    depends_on:
      etcd:
        condition: service_healthy
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      POSTGRES_DB: postgres
      CITUS_ENABLE: "true"
      CITUS_GROUP: 0
      PATRONI_ENABLE: "true"
      PATRONI_SCOPE: citus-cluster
      PATRONI_NAME: citus-coordinator-1
      PATRONI_REST_HOST: 0.0.0.0
      PATRONI_REST_PORT: "8008"
      PATRONI_SYNCHRONOUS_MODE: "true"
      PATRONI_SYNCHRONOUS_MODE_TYPE: quorum
      PATRONI_REPLICATION_USER: replicator
      PATRONI_REPLICATION_PASSWORD: replicatorpass
      ETCD_HOST: etcd
      ETCD_PORT: "2379"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_CONNECT_HOST: postgresql_c_1
      POSTGRESQL_LISTEN_HOST: 0.0.0.0
      POSTGRESQL_PORT: 5432
      PGBACKREST_ENABLE: "true"
    ports:
      - "5432:5432"
      - "8008:8008"
    volumes:
      - postgresql_c_1:/usr/local/pgsql/data
      - pgbackrest_backup:/var/lib/pgbackrest
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "postgres"]
      interval: 10s
      timeout: 5s
      retries: 12
    restart: unless-stopped

  postgresql_w_1_1:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    pull_policy: never
    container_name: postgresql_w_1_1
    depends_on:
      etcd:
        condition: service_healthy
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      POSTGRES_DB: postgres
      CITUS_ENABLE: "true"
      CITUS_GROUP: 1
      PATRONI_ENABLE: "true"
      PATRONI_SCOPE: citus-cluster
      PATRONI_NAME: citus-worker-1-1
      PATRONI_REST_HOST: 0.0.0.0
      PATRONI_REST_PORT: "8008"
      PATRONI_SYNCHRONOUS_MODE: "true"
      PATRONI_SYNCHRONOUS_MODE_TYPE: quorum
      PATRONI_REPLICATION_USER: replicator
      PATRONI_REPLICATION_PASSWORD: replicatorpass
      ETCD_HOST: etcd
      ETCD_PORT: "2379"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_CONNECT_HOST: postgresql_w_1_1
      POSTGRESQL_LISTEN_HOST: 0.0.0.0
      POSTGRESQL_PORT: 5432
    ports:
      - "5434:5432"
      - "8009:8008"
    volumes:
      - postgresql_w_1_1:/usr/local/pgsql/data
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "postgres"]
      interval: 10s
      timeout: 5s
      retries: 12
    restart: unless-stopped

  postgresql_w_1_2:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    pull_policy: never
    container_name: postgresql_w_1_2
    depends_on:
      etcd:
        condition: service_healthy
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      POSTGRES_DB: postgres
      CITUS_ENABLE: "true"
      CITUS_GROUP: 1
      PATRONI_ENABLE: "true"
      PATRONI_SCOPE: citus-cluster
      PATRONI_NAME: citus-worker-1-2
      PATRONI_REST_HOST: 0.0.0.0
      PATRONI_REST_PORT: "8008"
      PATRONI_SYNCHRONOUS_MODE: "true"
      PATRONI_SYNCHRONOUS_MODE_TYPE: quorum
      PATRONI_REPLICATION_USER: replicator
      PATRONI_REPLICATION_PASSWORD: replicatorpass
      ETCD_HOST: etcd
      ETCD_PORT: "2379"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_CONNECT_HOST: postgresql_w_1_2
      POSTGRESQL_LISTEN_HOST: 0.0.0.0
      POSTGRESQL_PORT: 5432
    ports:
      - "5433:5432"
      - "8010:8008"
    volumes:
      - postgresql_w_1_2:/usr/local/pgsql/data
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "postgres"]
      interval: 10s
      timeout: 5s
      retries: 12
    restart: unless-stopped

  postgresql_c_2:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    pull_policy: never
    container_name: postgresql_c_2
    depends_on:
      etcd:
        condition: service_healthy
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      POSTGRES_DB: postgres
      CITUS_ENABLE: "true"
      CITUS_GROUP: 0
      PATRONI_ENABLE: "true"
      PATRONI_SCOPE: citus-cluster
      PATRONI_NAME: citus-coordinator-2
      PATRONI_REST_HOST: 0.0.0.0
      PATRONI_REST_PORT: "8008"
      PATRONI_SYNCHRONOUS_MODE: "true"
      PATRONI_SYNCHRONOUS_MODE_TYPE: quorum
      PATRONI_REPLICATION_USER: replicator
      PATRONI_REPLICATION_PASSWORD: replicatorpass
      ETCD_HOST: etcd
      ETCD_PORT: "2379"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_CONNECT_HOST: postgresql_c_2
      POSTGRESQL_LISTEN_HOST: 0.0.0.0
      POSTGRESQL_PORT: 5432
      PGBACKREST_ENABLE: "true"
    ports:
      - "5435:5432"
      - "8011:8008"
    volumes:
      - postgresql_c_2:/usr/local/pgsql/data
      - pgbackrest_backup:/var/lib/pgbackrest
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "postgres"]
      interval: 10s
      timeout: 5s
      retries: 12
    restart: unless-stopped

  postgresql_w_2_1:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    pull_policy: never
    container_name: postgresql_w_2_1
    depends_on:
      etcd:
        condition: service_healthy
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      POSTGRES_DB: postgres
      CITUS_ENABLE: "true"
      CITUS_GROUP: 2
      PATRONI_ENABLE: "true"
      PATRONI_SCOPE: citus-cluster
      PATRONI_NAME: citus-worker-2-1
      PATRONI_REST_HOST: 0.0.0.0
      PATRONI_REST_PORT: "8008"
      PATRONI_SYNCHRONOUS_MODE: "true"
      PATRONI_SYNCHRONOUS_MODE_TYPE: quorum
      PATRONI_REPLICATION_USER: replicator
      PATRONI_REPLICATION_PASSWORD: replicatorpass
      ETCD_HOST: etcd
      ETCD_PORT: "2379"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_CONNECT_HOST: postgresql_w_2_1
      POSTGRESQL_LISTEN_HOST: 0.0.0.0
      POSTGRESQL_PORT: 5432
    ports:
      - "5436:5432"
      - "8012:8008"
    volumes:
      - postgresql_w_2_1:/usr/local/pgsql/data
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "postgres"]
      interval: 10s
      timeout: 5s
      retries: 12
    restart: unless-stopped

  postgresql_w_2_2:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    pull_policy: never
    container_name: postgresql_w_2_2
    depends_on:
      etcd:
        condition: service_healthy
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      POSTGRES_DB: postgres
      CITUS_ENABLE: "true"
      CITUS_GROUP: 2
      PATRONI_ENABLE: "true"
      PATRONI_SCOPE: citus-cluster
      PATRONI_NAME: citus-worker-2-2
      PATRONI_REST_HOST: 0.0.0.0
      PATRONI_REST_PORT: "8008"
      PATRONI_SYNCHRONOUS_MODE: "true"
      PATRONI_SYNCHRONOUS_MODE_TYPE: quorum
      PATRONI_REPLICATION_USER: replicator
      PATRONI_REPLICATION_PASSWORD: replicatorpass
      ETCD_HOST: etcd
      ETCD_PORT: "2379"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_CONNECT_HOST: postgresql_w_2_2
      POSTGRESQL_LISTEN_HOST: 0.0.0.0
      POSTGRESQL_PORT: 5432
    ports:
      - "5437:5432"
      - "8013:8008"
    volumes:
      - postgresql_w_2_2:/usr/local/pgsql/data
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "postgres"]
      interval: 10s
      timeout: 5s
      retries: 12
    restart: unless-stopped

  postgresql_c_3:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    pull_policy: never
    container_name: postgresql_c_3
    depends_on:
      etcd:
        condition: service_healthy
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      POSTGRES_DB: postgres
      CITUS_ENABLE: "true"
      CITUS_GROUP: 0
      PATRONI_ENABLE: "true"
      PATRONI_SCOPE: citus-cluster
      PATRONI_NAME: citus-coordinator-3
      PATRONI_REST_HOST: 0.0.0.0
      PATRONI_REST_PORT: "8008"
      PATRONI_SYNCHRONOUS_MODE: "true"
      PATRONI_SYNCHRONOUS_MODE_TYPE: quorum
      PATRONI_REPLICATION_USER: replicator
      PATRONI_REPLICATION_PASSWORD: replicatorpass
      ETCD_HOST: etcd
      ETCD_PORT: "2379"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_CONNECT_HOST: postgresql_c_3
      POSTGRESQL_LISTEN_HOST: 0.0.0.0
      POSTGRESQL_PORT: 5432
      PGBACKREST_ENABLE: "true"
    ports:
      - "5438:5432"
      - "8014:8008"
    volumes:
      - postgresql_c_3:/usr/local/pgsql/data
      - pgbackrest_backup:/var/lib/pgbackrest
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "postgres"]
      interval: 10s
      timeout: 5s
      retries: 12
    restart: unless-stopped

  postgresql_w_1_3:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    pull_policy: never
    container_name: postgresql_w_1_3
    depends_on:
      etcd:
        condition: service_healthy
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      POSTGRES_DB: postgres
      CITUS_ENABLE: "true"
      CITUS_GROUP: 1
      PATRONI_ENABLE: "true"
      PATRONI_SCOPE: citus-cluster
      PATRONI_NAME: citus-worker-1-3
      PATRONI_REST_HOST: 0.0.0.0
      PATRONI_REST_PORT: "8008"
      PATRONI_SYNCHRONOUS_MODE: "true"
      PATRONI_SYNCHRONOUS_MODE_TYPE: quorum
      PATRONI_REPLICATION_USER: replicator
      PATRONI_REPLICATION_PASSWORD: replicatorpass
      ETCD_HOST: etcd
      ETCD_PORT: "2379"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_CONNECT_HOST: postgresql_w_1_3
      POSTGRESQL_LISTEN_HOST: 0.0.0.0
      POSTGRESQL_PORT: 5432
    ports:
      - "5439:5432"
      - "8015:8008"
    volumes:
      - postgresql_w_1_3:/usr/local/pgsql/data
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "postgres"]
      interval: 10s
      timeout: 5s
      retries: 12
    restart: unless-stopped

  postgresql_w_2_3:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    pull_policy: never
    container_name: postgresql_w_2_3
    depends_on:
      etcd:
        condition: service_healthy
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
      POSTGRES_DB: postgres
      CITUS_ENABLE: "true"
      CITUS_GROUP: 2
      PATRONI_ENABLE: "true"
      PATRONI_SCOPE: citus-cluster
      PATRONI_NAME: citus-worker-2-3
      PATRONI_REST_HOST: 0.0.0.0
      PATRONI_REST_PORT: "8008"
      PATRONI_SYNCHRONOUS_MODE: "true"
      PATRONI_SYNCHRONOUS_MODE_TYPE: quorum
      PATRONI_REPLICATION_USER: replicator
      PATRONI_REPLICATION_PASSWORD: replicatorpass
      ETCD_HOST: etcd
      ETCD_PORT: "2379"
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
      EXTERNAL_ACCESS_ENABLE: "true"
      EXTERNAL_ACCESS_METHOD: md5
      POSTGRESQL_CONNECT_HOST: postgresql_w_2_3
      POSTGRESQL_LISTEN_HOST: 0.0.0.0
      POSTGRESQL_PORT: 5432
    ports:
      - "5440:5432"
      - "8016:8008"
    volumes:
      - postgresql_w_2_3:/usr/local/pgsql/data
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "postgres"]
      interval: 10s
      timeout: 5s
      retries: 12
    restart: unless-stopped

# C: Coordinator
# W: Worker
volumes:
  postgresql_c_1:
  postgresql_c_2:
  postgresql_c_3:
  postgresql_w_1_1:
  postgresql_w_1_2:
  postgresql_w_1_3:
  postgresql_w_2_1:
  postgresql_w_2_2:
  postgresql_w_2_3:
  pgbackrest_backup:
  etcd_data:
```

---
sidebar_position: 1
---

# Patroni Integration

PgPool-II acts as a smart gateway between your applications and your database. Designed for high availability, our image provides seamless Patroni integration. By simply providing your Patroni endpoint via Environment Variables, PgPool-II automatically detects the cluster state, ensuring your app always connects to the correct primary node.

```yaml
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
      - --initial-cluster-token=postgres-cluster
    ports:
      - "2379:2379"
      - "2380:2380"
    volumes:
      - etcd_data:/etcd-data
    healthcheck:
      test: ["CMD", "etcdctl", "--endpoints=127.0.0.1:2379", "endpoint", "health"]
      interval: 5s
      timeout: 3s
      retries: 30

  postgresql1:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    container_name: postgresql1
    depends_on:
      etcd:
        condition: service_healthy
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
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
    volumes:
      - postgresql1_data:/usr/local/pgsql/data
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
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
    volumes:
      - postgresql2_data:/usr/local/pgsql/data
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
      PG_HBA_ADD_1: "host all all 172.18.0.0/16 trust"
    volumes:
      - postgresql3_data:/usr/local/pgsql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -h localhost -p 5432 -U postgres"]
      interval: 30s
      timeout: 10s
      retries: 3

  # PgPool-II Load Balancer
  pgpool:
    image: supanadit/pgpool-ii:4.6.3
    environment:
      # Backend PostgreSQL instances (comma-separated)
      PGPOOL_BACKENDS: "postgresql1:5432,postgresql2:5432,postgresql3:5432"
      PGPOOL_PATRONI_ENDPOINTS: "http://postgresql1:8008,http://postgresql2:8008,http://postgresql3:8008"
      
      # Optional: Backend weights for load balancing (default: equal weights)
      PGPOOL_BACKEND_WEIGHTS: "1,1,1"
      
      # Optional: Backend flags (default: ALLOW_TO_FAILOVER)
      PGPOOL_BACKEND_FLAGS: "ALLOW_TO_FAILOVER,ALLOW_TO_FAILOVER,ALLOW_TO_FAILOVER"
      
      # Backend authentication
      PGPOOL_BACKEND_PASSWORD: "postgres_password"
      
      # Pool configuration
      PGPOOL_NUM_INIT_CHILDREN: "32"
      PGPOOL_MAX_POOL: "4"
      PGPOOL_CHILD_LIFE_TIME: "300"
      
      # Load balancing
      PGPOOL_LOAD_BALANCE_MODE: "on"

      PGPOOL_MASTER_SLAVE_MODE: "on"
      PGPOOL_MASTER_SLAVE_SUB_MODE: "stream"
      
      # Health check
      PGPOOL_HEALTH_CHECK_TIMEOUT: "20"
      PGPOOL_HEALTH_CHECK_PERIOD: "10"
      
      # Authentication
      PGPOOL_ENABLE_POOL_HBA: "off"
    ports:
      - "5432:5432"  # PgPool-II port
      - "9898:9898"  # PCP port for administration
    depends_on:
      postgresql1:
        condition: service_healthy
      postgresql2:
        condition: service_healthy
      postgresql3:
        condition: service_healthy
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -h localhost -p 5432 -U postgres || exit 1"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 60s

volumes:
  etcd_data:
  postgresql1_data:
  postgresql2_data:
  postgresql3_data:
```

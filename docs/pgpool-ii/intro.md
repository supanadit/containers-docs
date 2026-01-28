---
id: pgpool-ii-intro-doc
slug: /pgpool-ii
title: PgPool-II
---

# PgPool-II

PgPool-II is a high-performance middleware that sits between PostgreSQL servers and their clients. It acts as a smart gateway that manages connection pooling, load balancing, and high availability, ensuring your database cluster remains scalable and resilient to failures.

```yaml
services:
  # Primary PostgreSQL instance
  postgres-1:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    environment:
      HA_MODE: native
      REPLICATION_ROLE: primary
      REPLICATION_USER: replicator
      REPLICATION_PASSWORD: replicator_password
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
    ports:
      - "5433:5432"
    volumes:
      - postgres1_data:/usr/local/pgsql/data
    networks:
      - pgpool_network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -h localhost -p 5432 -U postgres"]
      interval: 30s
      timeout: 10s
      retries: 3

  # Secondary PostgreSQL instance
  postgres-2:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    environment:
      HA_MODE: native
      REPLICATION_ROLE: replica
      PRIMARY_HOST: postgres-1
      PRIMARY_PORT: 5432
      REPLICATION_USER: replicator
      REPLICATION_PASSWORD: replicator_password
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
    ports:
      - "5434:5432"
    depends_on:
      postgres-1:
        condition: service_healthy
    volumes:
      - postgres2_data:/usr/local/pgsql/data
    networks:
      - pgpool_network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -h localhost -p 5432 -U postgres"]
      interval: 30s
      timeout: 10s
      retries: 3

  # Tertiary PostgreSQL instance
  postgres-3:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    environment:
      HA_MODE: native
      REPLICATION_ROLE: replica
      PRIMARY_HOST: postgres-1
      PRIMARY_PORT: 5432
      REPLICATION_USER: replicator
      REPLICATION_PASSWORD: replicator_password
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
    ports:
      - "5435:5432"
    depends_on:
      postgres-1:
        condition: service_healthy
    volumes:
      - postgres3_data:/usr/local/pgsql/data
    networks:
      - pgpool_network
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
      PGPOOL_BACKENDS: "postgres-1:5432,postgres-2:5432,postgres-3:5432"
      
      # Optional: Backend weights for load balancing (default: equal weights)
      PGPOOL_BACKEND_WEIGHTS: "1,1,1"
      
      # Optional: Backend flags (default: ALLOW_TO_FAILOVER)
      PGPOOL_BACKEND_FLAGS: "ALLOW_TO_FAILOVER,ALLOW_TO_FAILOVER,ALLOW_TO_FAILOVER"
      
      # Backend authentication
      PGPOOL_BACKEND_PASSWORD: "secret"
      
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
      postgres-1:
        condition: service_healthy
      postgres-2:
        condition: service_healthy
      postgres-3:
        condition: service_healthy
    networks:
      - pgpool_network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -h localhost -p 5432 -U postgres || exit 1"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 60s

volumes:
  postgres1_data:
  postgres2_data:
  postgres3_data:

networks:
  pgpool_network:
    driver: bridge
```

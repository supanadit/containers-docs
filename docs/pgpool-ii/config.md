---
sidebar_position: 2
---

# Configuration Reference

Environment variables for configuring PgPool-II.

## Required

| Variable | Description | Example |
|----------|-------------|---------|
| `PGPOOL_BACKENDS` | Comma-separated list of PostgreSQL backends | `"postgres-1:5432,postgres-2:5432,postgres-3:5432"` |

## Backend Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_BACKEND_WEIGHTS` | `"1,1,1,..."` | Comma-separated weights for load balancing |
| `PGPOOL_BACKEND_FLAGS` | `"ALLOW_TO_FAILOVER,..."` | Flags for each backend: `ALLOW_TO_FAILOVER`, `DISALLOW_TO_FAILOVER` |
| `PGPOOL_BACKEND_USER` | `postgres` | User for backend connections |
| `PGPOOL_BACKEND_PASSWORD` | (required) | Password for backend connections |

### Backend Flags

- `ALLOW_TO_FAILOVER` - Allow automatic failover to this backend
- `DISALLOW_TO_FAILOVER` - Never failover to this backend (useful for readonly replicas)

Example:
```yaml
environment:
  PGPOOL_BACKEND_FLAGS: "ALLOW_TO_FAILOVER,ALLOW_TO_FAILOVER,DISALLOW_TO_FAILOVER"
```

## Connection Settings

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_PORT` | `5432` | PgPool-II listening port |
| `PGPOOL_PCP_PORT` | `9898` | PCP (PgPool Control Protocol) port |
| `PGPOOL_LISTEN_ADDRESSES` | `*` | Listen addresses |

## Pool Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_NUM_INIT_CHILDREN` | `32` | Number of pre-forked child processes |
| `PGPOOL_MAX_POOL` | `4` | Maximum connections per child process |
| `PGPOOL_CHILD_LIFE_TIME` | `300` | Child process lifetime in seconds |
| `PGPOOL_CONNECTION_LIFE_TIME` | `0` | Connection lifetime in seconds (0 = unlimited) |
| `PGPOOL_CHILD_MAX_CONNECTIONS` | `0` | Maximum connections per child (0 = unlimited) |

## Load Balancing

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_LOAD_BALANCE_MODE` | `on` | Enable/disable load balancing |
| `PGPOOL_IGNORE_LEADING_WHITE_SPACE` | `on` | Ignore leading whitespace in SQL |

### Load Balance Weights

```yaml
environment:
  # primary gets 3x more traffic than each replica
  PGPOOL_BACKEND_WEIGHTS: "3,1,1"
```

## Health Check

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_HEALTH_CHECK_TIMEOUT` | `5` | Health check timeout in seconds |
| `PGPOOL_HEALTH_CHECK_PERIOD` | `10` | Health check interval in seconds |
| `PGPOOL_HEALTH_CHECK_USER` | `postgres` | User for health checks |
| `PGPOOL_HEALTH_CHECK_PASSWORD` | (from `PGPOOL_BACKEND_PASSWORD`) | Password for health checks |
| `PGPOOL_HEALTH_CHECK_DATABASE` | `postgres` | Database for health checks |
| `PGPOOL_HEALTH_CHECK_MAX_RETRIES` | `0` | Max retry attempts on failure |
| `PGPOOL_HEALTH_CHECK_RETRY_DELAY` | `1` | Seconds to wait before retry |

## Streaming Replication Check

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_SR_CHECK_PERIOD` | `10` | Streaming replication check interval in seconds |
| `PGPOOL_SR_CHECK_USER` | (from `PGPOOL_BACKEND_USER`) | User for replication check |
| `PGPOOL_SR_CHECK_PASSWORD` | (from `PGPOOL_BACKEND_PASSWORD`) | Password for replication check |
| `PGPOOL_SR_CHECK_DATABASE` | `postgres` | Database for replication check |
| `PGPOOL_DELAY_THRESHOLD` | `0` | Max replication delay in bytes (0 = disabled) |
| `PGPOOL_DELAY_THRESHOLD_BY_TIME` | `0` | Max replication delay in seconds |
| `PGPOOL_PREFER_LOWER_DELAY_STANDBY` | `off` | Prefer standby with lowest delay |

### Delay Threshold Example

```yaml
environment:
  # Only send reads to replicas if lag is less than 1MB
  PGPOOL_DELAY_THRESHOLD: "1048576"
  PGPOOL_PREFER_LOWER_DELAY_STANDBY: "on"
```

## Primary Node Detection

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_SEARCH_PRIMARY_NODE_TIMEOUT` | `10` | Timeout in seconds to find primary node |

## Failover Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_FAILOVER_COMMAND` | `""` | Command to execute on failover |

### Failover Command Example

```yaml
environment:
  PGPOOL_FAILOVER_COMMAND: |
    /usr/local/bin/failover.sh %d %h %P %m %M "%H" %n
```

See [PgPool-II documentation](https://www.pgpool.net/docs/latest/en/html/config-failover.html) for parameter details.

## Master/Slave Mode

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_MASTER_SLAVE_MODE` | `off` | Enable master/slave mode |
| `PGPOOL_MASTER_SLAVE_SUB_MODE` | `stream` | Sub mode: `stream` or `slony` |

```yaml
environment:
  PGPOOL_MASTER_SLAVE_MODE: "on"
  PGPOOL_MASTER_SLAVE_SUB_MODE: "stream"
```

## Query Router Mode

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_QUERYRouter_MODE` | `off` | Enable query router mode (for sharding) |

## Authentication

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_ENABLE_POOL_HBA` | `off` | Enable pool_hba.conf authentication |
| `PGPOOL_POOL_PASSWD` | `pool_passwd` | Password file name |

## Logging

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_LOG_MIN_MESSAGES` | `warning` | Minimum log level |
| `PGPOOL_DEBUG` | `false` | Enable debug logging |

### Log Levels

- `debug5`, `debug4`, `debug3`, `debug2`, `debug1`
- `info`, `notice`, `warning`, `error`, `log`
- `fatal`, `panic`

## PCP (PgPool Control Protocol)

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_PCP_TIMEOUT` | `10` | PCP operation timeout in seconds |
| `PGPOOL_PCP_NUM_CHILDREN` | `32` | Number of PCP child processes |

## Watchdog

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_ENABLE_WATCHDOG` | `off` | Enable watchdog for HA |
| `PGPOOL_WD_HOSTNAME` | (none) | Partner PgPool-II hostname |
| `PGPOOL_WD_PORT` | `9000` | Watchdog port |
| `PGPOOL_DELegate_IP` | (none) | Virtual IP for failover |

## Patroni Integration

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_PATRONI_ENDPOINTS` | `""` | Comma-separated Patroni REST API endpoints |
| `PGPOOL_PATRONI_TIMEOUT` | `10` | Timeout for Patroni API queries in seconds |

### Patroni Example

```yaml
environment:
  PGPOOL_BACKENDS: "postgresql1:5432,postgresql2:5432,postgresql3:5432"
  PGPOOL_PATRONI_ENDPOINTS: "http://postgresql1:8008,http://postgresql2:8008,http://postgresql3:8008"
```

See [Patroni Integration](./patroni) for detailed setup.

## Hint-based Query Routing

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_ENABLE_HINT_QUERY` | `off` | Enable hint-based query routing |

### Using Hints

Enable load balancing with per-query hints:

```yaml
environment:
  PGPOOL_ENABLE_HINT_QUERY: "on"
```

Then use SQL comments to direct queries:

```sql
-- Direct to primary
/*INSERT*/ INSERT INTO users VALUES (1, 'test');

-- Direct to replica
/*READONLY*/ SELECT * FROM users;
```

## SSL/TLS Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_SSL` | `off` | Enable SSL |
| `PGPOOL_SSL_KEY` | (none) | Path to SSL key |
| `PGPOOL_SSL_CERT` | (none) | Path to SSL certificate |

## Memory Cache

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_MEMORY_CACHE_MODE` | `off` | Enable memory cache mode |
| `PGPOOL_MEMQCACHE_CACHE_BLOCKS` | `4096` | Number of cache blocks |
| `PGPOOL_MEMQCACHE_BLOCK_SIZE` | `1048576` | Block size in bytes |
| `PGPOOL_MEMQCACHE_MAXCACHE` | `20` | Maximum cached queries |

## Replication Check

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_REPLICATION_CHECK` | `off` | Enable replication check |

## Inline Query Validation

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_VALIDATE_QUERY` | `off` | Validate SQL before sending to backends |

## Advanced Settings

| Variable | Default | Description |
|----------|---------|-------------|
| `PGPOOL_CONFIG_DIR` | `/usr/local/pgpool/etc` | Configuration directory |
| `PGPOOL_LOG_DIR` | `/var/log/pgpool` | Log directory |
| `PGPOOL_RUN_DIR` | `/var/run/pgpool` | Runtime directory |
| `PGPOOL_USER` | `postgres` | User to run PgPool-II as |

## Complete Example

```yaml
services:
  pgpool:
    image: supanadit/pgpool-ii:4.6.3
    environment:
      # Backends
      PGPOOL_BACKENDS: "postgres-1:5432,postgres-2:5432,postgres-3:5432"
      PGPOOL_BACKEND_PASSWORD: "secret"
      PGPOOL_BACKEND_WEIGHTS: "1,1,1"
      PGPOOL_BACKEND_FLAGS: "ALLOW_TO_FAILOVER,ALLOW_TO_FAILOVER,DISALLOW_TO_FAILOVER"
      
      # Pool configuration
      PGPOOL_NUM_INIT_CHILDREN: "32"
      PGPOOL_MAX_POOL: "4"
      PGPOOL_CHILD_LIFE_TIME: "300"
      
      # Load balancing
      PGPOOL_LOAD_BALANCE_MODE: "on"
      PGPOOL_IGNORE_LEADING_WHITE_SPACE: "on"
      
      # Master/slave mode
      PGPOOL_MASTER_SLAVE_MODE: "on"
      PGPOOL_MASTER_SLAVE_SUB_MODE: "stream"
      
      # Health check
      PGPOOL_HEALTH_CHECK_TIMEOUT: "20"
      PGPOOL_HEALTH_CHECK_PERIOD: "10"
      
      # Replication delay check
      PGPOOL_SR_CHECK_PERIOD: "10"
      PGPOOL_DELAY_THRESHOLD: "1048576"
      PGPOOL_PREFER_LOWER_DELAY_STANDBY: "off"
      
      # Authentication
      PGPOOL_ENABLE_POOL_HBA: "off"
    ports:
      - "5432:5432"
      - "9898:9898"
```

## Related

- [Intro](./intro) - Basic setup
- [Patroni](./patroni) - Patroni integration
- [Troubleshooting](./troubleshooting) - Common issues
---
sidebar_position: 4
---

# Tips & Tricks

Best practices and common pitfalls when using PgPool-II.

## Common Pitfalls

### SR_CHECK_PERIOD = 0

**Problem:** All SELECT queries go to replicas, primary never gets reads.

```yaml
# WRONG - disables primary detection
- name: PGPOOL_SR_CHECK_PERIOD
  value: "0"
```

**Why:** Without streaming replication check enabled, PgPool-II cannot detect which node is primary. It defaults to sending all reads to replicas.

**Fix:**
```yaml
- name: PGPOOL_SR_CHECK_PERIOD
  value: "10"
- name: PGPOOL_SR_CHECK_USER
  value: "replicator"
- name: PGPOOL_SR_CHECK_PASSWORD
  value: "<replicator_password>"
```

### Missing SR_CHECK Credentials

**Problem:** Health checks pass but SR check fails silently.

PgPool-II requires a replication user for streaming replication checks:

```yaml
# Ensure these are set
- name: PGPOOL_SR_CHECK_USER
  value: "replicator"
- name: PGPOOL_SR_CHECK_PASSWORD
  value: "<replicator_password>"
```

On PostgreSQL, create the user:
```sql
CREATE USER replicator WITH REPLICATION ENCRYPTED PASSWORD '<password>';
```

### Wrong Backend Weights

**Problem:** Load balancing feels uneven or doesn't work as expected.

```yaml
# WRONG - all zeros means no traffic to those backends
- name: PGPOOL_BACKEND_WEIGHTS
  value: "0,0"
```

## Load Balancing Best Practices

### Enable Streaming Replication Check

Always enable SR_CHECK when using master/slave mode:

```yaml
- name: PGPOOL_MASTER_SLAVE_MODE
  value: "on"
- name: PGPOOL_MASTER_SLAVE_SUB_MODE
  value: "stream"
- name: PGPOOL_SR_CHECK_PERIOD
  value: "10"
```

### Set Appropriate Delay Threshold

Prevent stale reads by configuring replication lag threshold:

```yaml
# Only send reads to replicas if lag is less than 1MB
- name: PGPOOL_DELAY_THRESHOLD
  value: "1048576"  # 1MB in bytes

# Prefer standby with lowest delay
- name: PGPOOL_PREFER_LOWER_DELAY_STANDBY
  value: "on"
```

### Prioritize Primary for Reads

Use backend weights to control traffic distribution:

```yaml
# Primary gets 3x more reads than each replica
- name: PGPOOL_BACKEND_WEIGHTS
  value: "3,1,1"
```

### Hint-Based Query Routing

For precise control, enable hint-based routing:

```yaml
- name: PGPOOL_ENABLE_HINT_QUERY
  value: "on"
```

Then use SQL comments:
```sql
/*READONLY*/ SELECT * FROM users;
 /*INSERT*/ INSERT INTO orders VALUES (1);
 /*DELETE*/ DELETE FROM cart WHERE user_id = 1;
```

## Health Check Tuning

### Recommended Settings

```yaml
- name: PGPOOL_HEALTH_CHECK_PERIOD
  value: "10"
- name: PGPOOL_HEALTH_CHECK_TIMEOUT
  value: "5"
- name: PGPOOL_HEALTH_CHECK_MAX_RETRIES
  value: "3"
- name: PGPOOL_HEALTH_CHECK_RETRY_DELAY
  value: "3"
```

### Avoid Flapping Backends

If backends frequently change status (up/down/up), increase intervals:

```yaml
- name: PGPOOL_HEALTH_CHECK_PERIOD
  value: "30"  # Slower checks
- name: PGPOOL_HEALTH_CHECK_RETRY_DELAY
  value: "5"   # Longer retry delay
```

### Dedicated Health Check User

Create a dedicated user for health checks (optional but more secure):

```sql
CREATE USER healthcheck WITH ENCRYPTED PASSWORD '<password>';
GRANT SELECT ON pg_stat_database TO healthcheck;
```

## High Availability Tips

### DISALLOW_TO_FAILOVER for Static Replicas

Use `DISALLOW_TO_FAILOVER` when you don't want PgPool-II to automatically failover to certain backends:

```yaml
- name: PGPOOL_BACKEND_FLAGS
  value: "ALLOW_TO_FAILOVER,DISALLOW_TO_FAILOVER"
```

Common use cases:
- Read-only replicas that should never become primary
- Backup nodes
- Static replica names that shouldn't change

### Failover Command

Configure automatic failover script:

```yaml
- name: PGPOOL_FAILOVER_COMMAND
  value: |
    /usr/local/bin/failover.sh %d %h %P %m %M "%H" %n
```

Example failover script:
```bash
#!/bin/bash
# failover.sh
PRIMARY_NODE=$1
NEW_PRIMARY_HOST=$2
OLD_PRIMARY_NODE=$3

# Update Patroni or your HA solution
curl -X PUT http://patroni-cluster/patroni/$NEW_PRIMARY_HOST/reload
```

### Watchdog for True HA

For production, configure Watchdog to prevent split-brain:

```yaml
- name: PGPOOL_ENABLE_WATCHDOG
  value: "on"
- name: PGPOOL_WD_HOSTNAME
  value: "pgpool-2.erp-db-postgresql.svc.cluster.local"
- name: PGPOOL_WD_PORT
  value: "9000"
- name: PGPOOL_DELegate_IP
  value: "10.0.0.100"
- name: PGPOOL_WD_LIFECHECK_METHOD
  value: "heartbeat"
- name: PGPOOL_WD_INTERVAL
  value: "10"
- name: PGPOOL_WD_PRIORITY
  value: "1"
```

**Watchdog tips:**
- Set different `WD_PRIORITY` on each PgPool-II instance to control which becomes leader
- Use `heartbeat` method for faster failover detection
- Ensure network connectivity between Watchdog ports (9000)

## Performance Tips

### Memory Cache for Query Acceleration

Enable memory cache to reduce database load:

```yaml
- name: PGPOOL_MEMORY_CACHE_MODE
  value: "on"
- name: PGPOOL_MEMQCACHE_TOTAL_SIZE
  value: "1024"  # 1GB cache
- name: PGPOOL_MEMQCACHE_EXPIRE_TIME
  value: "10000"  # 10 second expiration
```

**When to use:**
- Repeated queries on relatively static data
- Reducing load on primary for read-heavy workloads

**When NOT to use:**
- Highly dynamic data that changes frequently
- When fresh data is critical (e.g., financial transactions)

### Pool Sizing Guidelines

| Workload | NUM_INIT_CHILDREN | MAX_POOL |
|----------|-------------------|----------|
| Light | 16-32 | 2-4 |
| Medium | 32-64 | 4-8 |
| Heavy | 64-128 | 8-16 |

```yaml
- name: PGPOOL_NUM_INIT_CHILDREN
  value: "32"
- name: PGPOOL_MAX_POOL
  value: "4"
- name: PGPOOL_CHILD_LIFE_TIME
  value: "600"
```

### SSL/TLS Configuration

Enable SSL for encrypted connections:

```yaml
- name: PGPOOL_SSL
  value: "on"
- name: PGPOOL_SSL_KEY
  value: "/etc/ssl/private/server.key"
- name: PGPOOL_SSL_CERT
  value: "/etc/ssl/certs/server.crt"
- name: PGPOOL_SSL_SECURITY_MODE
  value: "postgresql"
```

**Security modes:**
- `postgresql` - Encrypt only data, allow service restart without re-sslkey
- `transfer` - Full SSL connection (requires restart to change key)

### When to Disable Features

Disable if not needed for better performance:

```yaml
# Disable if not using hint queries
- name: PGPOOL_ENABLE_HINT_QUERY
  value: "off"

# Disable memory cache if data is highly dynamic
- name: PGPOOL_MEMORY_CACHE_MODE
  value: "off"

# Disable pool_hba if using application-level auth
- name: PGPOOL_ENABLE_POOL_HBA
  value: "off"
```

### Monitoring with PCP

Monitor PgPool-II status:

```bash
# Show pool status
pcp_pool_status -h localhost -p 9898 -U postgres

# Show backend nodes
pcp_node_info -h localhost -p 9898 -U postgres

# Show process status
psql -h localhost -p 5432 -U postgres -c "SELECT * FROM pgpool_catalog.pgpool_status;"
```

## Patroni Integration Tips

### Verify Endpoints

Always verify Patroni REST API is accessible:

```bash
curl -s http://postgresql1:8008/patroni | jq '.role'
```

Expected output: `{"role":"master"}` or `{"role":"replica"}`

### Timeout Recommendations

```yaml
# Patroni API timeout
- name: PGPOOL_PATRONI_TIMEOUT
  value: "10"

# Primary search timeout
- name: PGPOOL_SEARCH_PRIMARY_NODE_TIMEOUT
  value: "30"
```

### Common Patroni Mistakes

1. **Wrong endpoint format** - Must include port:
   ```yaml
   # WRONG
   PGPOOL_PATRONI_ENDPOINTS: "postgresql1,postgresql2"
   
   # CORRECT
   PGPOOL_PATRONI_ENDPOINTS: "http://postgresql1:8008,http://postgresql2:8008"
   ```

2. **Firewall blocking port 8008** - PgPool-II needs access to Patroni REST API

3. **Missing health check credentials** - SR_CHECK still needs to be enabled

## Architecture: PgPool-II vs PgBouncer vs Native HA

Choose the right solution based on your requirements:

### PgPool-II

**Best for:**
- Read/write splitting (load balancing SELECTs)
- Connection pooling with multiple backends
- Automatic failover with streaming replication
- Query caching
- Watchdog for high availability

**Not ideal for:**
- Simple connection pooling to single PostgreSQL
- When you don't need load balancing
- Very high throughput where overhead matters

### PgBouncer

**Best for:**
- Simple connection pooling (transaction or session mode)
- High connection concurrency
- Lightweight overhead
- Connection limiting per database/user

**Not ideal for:**
- Read/write splitting
- Multiple backend support
- Built-in failover

### Native Streaming Replication

**Best for:**
- Simple primary-replica setup
- No additional middleware
- Lower complexity
- When automatic failover is handled by external tool (Patroni, etcd)

**Not ideal for:**
- Read/write splitting without additional tools
- Connection pooling
- Complex failover scenarios without external coordination

### Decision Matrix

| Feature | PgPool-II | PgBouncer | Native HA |
|---------|-----------|-----------|-----------|
| Connection Pooling | Yes | Yes | No |
| Load Balancing | Yes | No | No |
| Read/Write Split | Yes | No | No |
| Auto Failover | Yes | No | No |
| Multiple Backends | Yes | Single | Yes |
| Complexity | High | Low | Medium |
| Overhead | Medium | Very Low | None |
| HA (Watchdog) | Yes | No | No |

### Common Setups

**Simple & Lightweight:**
```
Client → PgBouncer → PostgreSQL (single or with repmgr)
```

**Load Balanced with HA:**
```
Client → PgPool-II → PostgreSQL Primary
                    ↘ PostgreSQL Replica 1
                    ↘ PostgreSQL Replica 2
```

**Full Stack with Patroni:**
```
Client → PgPool-II → Patroni Cluster
                    ↘ etcd/Consul
```

**Recommended Combination:**
- Use **PgBouncer** for transaction-level connection pooling
- Use **Patroni** for automatic failover and replication management
- Use **PgPool-II** in front when you need read/write splitting

## Related

- [Intro](/docs/pgpool-ii) - Basic setup
- [Patroni](/docs/pgpool-ii/patroni) - Patroni integration
- [Config](/docs/pgpool-ii/config) - Configuration reference
- [Troubleshooting](/docs/pgpool-ii/troubleshooting) - Common issues
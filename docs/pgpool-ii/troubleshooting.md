---
sidebar_position: 3
---

# Troubleshooting

Common issues with PgPool-II and their solutions.

## Connection Issues

### Connection Refused to Backends

**Symptom:**
```
ERROR: unable to connect to database server
```

**Causes and Solutions:**

1. **Check backend accessibility:**
   ```bash
   docker exec pgpool ping -c 3 postgres-1
   ```

2. **Verify backend addresses in PGPOOL_BACKENDS:**
   ```bash
   docker exec pgpool sh -c 'echo $PGPOOL_BACKENDS'
   ```

3. **Check PostgreSQL is accepting connections:**
   ```bash
   docker exec postgres-1 pg_isready -h localhost -p 5432 -U postgres
   ```

4. **Verify firewall/network settings:**
   Ensure PgPool-II container can reach backend PostgreSQL containers on the network.

### Authentication Failed

**Symptom:**
```
ERROR: authentication failed for user "postgres"
```

**Causes and Solutions:**

1. **Check backend password:**
   ```bash
   docker exec pgpool env | grep PGPOOL_BACKEND_PASSWORD
   ```

2. **Verify pg_hba.conf on backends allows PgPool-II:**
   ```bash
   docker exec postgres-1 cat /opt/containers/data/pg_hba.conf | grep -v "^#" | grep -v "^$"
   ```

3. **If using md5 authentication, ensure password is correct:**
   The password in `PGPOOL_BACKEND_PASSWORD` must match the PostgreSQL user password.

### Cannot Connect to PgPool-II

**Symptom:**
```
psql: error: could not connect to server: Connection refused
```

**Solutions:**

1. **Check PgPool-II is running:**
   ```bash
   docker exec pgpool ps aux | grep pgpool
   ```

2. **Verify port mapping:**
   ```bash
   docker port pgpool
   ```

3. **Check PgPool-II logs:**
   ```bash
   docker logs pgpool
   ```

## Load Balancing Issues

### Load Balancing Not Working

**Symptom:** All queries go to the primary/one backend.

**Causes and Solutions:**

1. **Verify load balancing is enabled:**
   ```bash
   docker exec pgpool psql -h localhost -p 5432 -U postgres -c "SHOW load_balance_mode;"
   ```

2. **Enable if disabled:**
   Set `PGPOOL_LOAD_BALANCE_MODE: "on"` in your compose file.

3. **Check query is load-balanceable:**
   Not all queries are load-balanceable. Writes, temporary tables, and transactions with modifications won't be load-balanced.

4. **Check backend weights:**
   If using `PGPOOL_BACKEND_WEIGHTS`, verify the weights are not all set to 0.

### Reads Going to Wrong Backend

**Symptom:** Read queries going to primary instead of replicas.

**Solutions:**

1. **Use `pgpool_hint_plan` extension:**
   
   Add `PGPOOL_ENABLE_HINT_QUERY: "on"` and use hint comments:
   ```sql
   /*READONLY*/ SELECT * FROM users WHERE id = 1;
   ```

2. **Check delay threshold:**
   If `PGPOOL_DELAY_THRESHOLD` is set too low, replicas with any lag won't receive reads.

## Health Check Issues

### Backends Marked as Down

**Symptom:**
```
WARNING: database backend #1 (postgres-1) down
```

**Solutions:**

1. **Check health check user has access:**
   ```bash
   docker exec postgres-1 psql -h localhost -U postgres -c "SELECT 1;"
   ```

2. **Adjust health check timeout:**
   ```yaml
   environment:
     PGPOOL_HEALTH_CHECK_TIMEOUT: "30"
     PGPOOL_HEALTH_CHECK_PERIOD: "15"
   ```

3. **Check health check database exists:**
   Default is `postgres`. Create it if needed:
   ```bash
   docker exec postgres-1 psql -U postgres -c "CREATE DATABASE postgres;"
   ```

### Flapping Backend Status

**Symptom:** Backend status changes between up and down frequently.

**Solutions:**

1. **Increase health check period:**
   ```yaml
   PGPOOL_HEALTH_CHECK_PERIOD: "30"
   ```

2. **Increase retry delay:**
   ```yaml
   PGPOOL_HEALTH_CHECK_RETRY_DELAY: "5"
   ```

3. **Add retry attempts:**
   ```yaml
   PGPOOL_HEALTH_CHECK_MAX_RETRIES: "2"
   ```

## Replication Issues

### Replication Delay Not Detected

**Symptom:** PgPool-II doesn't detect replication lag.

**Solutions:**

1. **Enable streaming replication check:**
   ```yaml
   environment:
     PGPOOL_MASTER_SLAVE_MODE: "on"
     PGPOOL_MASTER_SLAVE_SUB_MODE: "stream"
   ```

2. **Check SR check user credentials:**
   ```yaml
   PGPOOL_SR_CHECK_USER: "replicator"
   PGPOOL_SR_CHECK_PASSWORD: "replicator_password"
   ```

3. **Set delay threshold:**
   ```yaml
   PGPOOL_DELAY_THRESHOLD: "100000"
   ```

### Stale Reads from Replica

**Symptom:** Application reads stale data from replicas.

**Solutions:**

1. **Use `prefer_lower_delay_standby`:**
   ```yaml
   PGPOOL_PREFER_LOWER_DELAY_STANDBY: "on"
   ```

2. **Check replication lag:**
   ```sql
   SELECT * FROM pg_stat_replication;
   ```

3. **Consider synchronous replication** if zero lag is critical.

## Patroni Integration Issues

### Primary Not Detected

**Symptom:** PgPool-II connects to a replica instead of primary.

**Solutions:**

1. **Verify Patroni endpoints:**
   ```bash
   docker exec pgpool env | grep PGPOOL_PATRONI_ENDPOINTS
   ```

2. **Check Patroni REST API access:**
   ```bash
   docker exec pgpool curl -s http://postgresql1:8008/patroni
   ```

3. **Increase search timeout:**
   ```yaml
   PGPOOL_SEARCH_PRIMARY_NODE_TIMEOUT: "30"
   ```

4. **Check Patroni timeout:**
   ```yaml
   PGPOOL_PATRONI_TIMEOUT: "30"
   ```

### Patroni Endpoints Unreachable

**Symptom:**
```
ERROR: could not fetch Patroni endpoints
```

**Solutions:**

1. **Verify network connectivity:**
   ```bash
   docker exec pgpool curl -s http://postgresql1:8008/
   ```

2. **Check Patroni is running:**
   ```bash
   docker exec postgresql1 ps aux | grep patroni
   ```

3. **Verify Patroni REST port:**
   Default is 8008. Check `PATRONI_REST_PORT` matches.

## Performance Issues

### High Connection Count

**Symptom:**
```
ERROR: sorry, too many clients already
```

**Solutions:**

1. **Increase pool size:**
   ```yaml
   PGPOOL_NUM_INIT_CHILDREN: "64"
   PGPOOL_MAX_POOL: "8"
   ```

2. **Check for connection leaks:**
   ```bash
   docker exec pgpool pcp_pool_status -h localhost -p 9898 -U postgres | grep-used
   ```

3. **Adjust child lifetime:**
   ```yaml
   PGPOOL_CHILD_LIFE_TIME: "600"
   ```

### Slow Query Performance

**Symptom:** Queries take longer through PgPool-II than direct to PostgreSQL.

**Solutions:**

1. **Check PgPool-II log for slow queries:**
   ```bash
   docker logs pgpool | grep -i slow
   ```

2. **Disable unnecessary features:**
   - Turn off health checks if not needed
   - Disable query logging

3. **Increase child processes:**
   ```yaml
   PGPOOL_NUM_INIT_CHILDREN: "64"
   ```

## Failover Issues

### Failover Not Triggering

**Symptom:** Backend is down but PgPool-II doesn't fail over.

**Solutions:**

1. **Verify backend flags:**
   ```bash
   docker exec pgpool psql -h localhost -p 5432 -U postgres -c "SHOW backend_hostname;"
   ```

2. **Check ALLOW_TO_FAILOVER flag:**
   Should be set on backends that can fail over:
   ```yaml
   PGPOOL_BACKEND_FLAGS: "ALLOW_TO_FAILOVER,ALLOW_TO_FAILOVER,ALLOW_TO_FAILOVER"
   ```

3. **Configure failover command:**
   ```yaml
   PGPOOL_FAILOVER_COMMAND: "/path/to/failover_script"
   ```

### Failover Script Not Executing

**Symptom:** `PGPOOL_FAILOVER_COMMAND` is set but doesn't run.

**Solutions:**

1. **Check script exists and is executable:**
   ```bash
   docker exec pgpool ls -l /path/to/failover_script
   ```

2. **Verify script has correct shebang:**
   ```bash
   #!/bin/bash
   ```

3. **Check logs for errors:**
   ```bash
   docker logs pgpool | grep -i failover
   ```

## Administration

### PCP Commands Not Working

**Symptom:** `pcp_` commands fail with connection refused.

**Solutions:**

1. **Verify PCP port is exposed:**
   ```yaml
   ports:
     - "9898:9898"
   ```

2. **Check PCP user authentication:**
   Default user is `postgres`. Could be configured differently.

### View PgPool-II Status

```bash
# Show pool status
docker exec pgpool pcp_pool_status -h localhost -p 9898 -U postgres

# Show backend nodes
docker exec pgpool pcp_node_info -h localhost -p 9898 -U postgres -n 0

# Show pool configuration
docker exec pgpool psql -h localhost -p 5432 -U postgres -c "SHOW all;"
```

## Diagnostic Queries

```sql
-- 1. Show all settings
SHOW all;

-- 2. Show backend nodes
SHOW backend_hostname;
SHOW backend_port;

-- 3. Show pool status
SELECT * FROM pgpool_catalog.pgpool_status;

-- 4. Show connections
SELECT * FROM pgpool_catalog.pgpool_conns;

-- 5. Show process status
SELECT * FROM pgpool_catalog.pgpool_processes;
```

## Log Analysis

```bash
# View PgPool-II logs
docker logs pgpool

# Search for errors
docker logs pgpool 2>&1 | grep -i error

# Search for warnings
docker logs pgpool 2>&1 | grep -i warn

# Follow logs in real-time
docker logs -f pgpool
```

## Getting Help

### Essential Diagnostics

Run these commands to diagnose issues:

```bash
# 1. Check PgPool-II process
docker exec pgpool ps aux | grep pgpool

# 2. Check configuration
docker exec pgpool psql -h localhost -p 5432 -U postgres -c "SHOW all;"

# 3. Check backend status
docker exec pgpool pcp_node_info -h localhost -p 9898 -U postgres -n 0

# 4. Check logs
docker logs pgpool

# 5. Check network
docker exec pgpool cat /etc/hosts
```

## Related

- [Intro](/docs/pgpool-ii) - Basic PgPool-II setup
- [Patroni](/docs/pgpool-ii/patroni) - Patroni integration
---
sidebar_position: 5
---

# Troubleshooting

Common issues with Native HA and their solutions.

## Replication Issues

### Replica Shows `potential` Instead of `sync`

**Symptom:**
```sql
SELECT application_name, sync_state FROM pg_stat_replication;
```
```
 application_name | sync_state
-----------------+------------
 replica1        | sync
 replica2        | potential
```

**Cause:** This is **expected behavior** for single sync mode (`synchronous_standby_names = '*'`). Only one replica can be `sync` at a time.

**Solutions:**

1. **Use quorum sync mode** if you need multiple sync replicas:
   ```bash
   pg-reload-sync-config.sh --sync-mode=true --sync-count=2 --sync-replicas="replica1,replica2"
   ```

2. **Accept this behavior** if single sync is acceptable for your use case.

### All Replicas Show `async`

**Symptom:**
```sql
SELECT application_name, sync_state FROM pg_stat_replication;
```
```
 application_name | sync_state
-----------------+------------
 replica1        | async
 replica2        | async
```

**Cause:** `synchronous_standby_names` is not set (async mode) or is empty.

**Solution:**
1. Check current configuration:
   ```sql
   SHOW synchronous_standby_names;
   ```

2. If empty, enable sync mode:
   ```bash
   pg-reload-sync-config.sh --sync-mode=true
   ```

### Replication Not Working at All

**Symptom:**
```sql
SELECT * FROM pg_stat_replication;
```
```
(0 rows)
```

**Causes and Solutions:**

1. **Check replica connectivity:**
   ```bash
   # On replica
   psql -h localhost -U postgres -c "SELECT 1;"
   ```

2. **Check pg_hba.conf on primary:**
   ```bash
   # Primary should allow replication connections
   host replication replicator 0.0.0.0/0 scram-sha-256
   ```

3. **Check replica is not in recovery:**
   ```sql
   -- On replica
   SELECT pg_is_in_recovery();
   ```
   Should return `t` (true) for a replica.

4. **Check standby.signal exists:**
   ```bash
   ls -la /usr/local/pgsql/data/standby.signal
   ```

## Configuration Issues

### "canceling statement due to conflict with recovery"

**Symptom:** Errors appearing on replica queries:

```
ERROR: canceling statement due to conflict with recovery
DETAIL: User query was conflicting with recovery of X
```

**Root Cause:** WAL replay from primary conflicts with active queries on replica — typically during VACUUM or schema changes on primary while replica is running long queries.

**Our Default Fix (Already Applied):** This container auto-configures replica-side settings so you don't have to tune manually:

| Setting | PostgreSQL Default | Our Default | Purpose |
|---------|-------------------|-------------|---------|
| `hot_standby_feedback` | `off` | `on` | Replica tells primary which rows it's actively using |
| `max_standby_streaming_delay` | `30s` | `-1` (infinite) | Never cancel conflicting queries |
| `max_standby_archive_delay` | `30s` | `-1` (infinite) | Never cancel for archived WAL |

**Why `-1` (infinite)?** PostgreSQL's default 30s is often too short for read-heavy workloads with long analytical queries. Setting `-1` means the replica will wait indefinitely rather than interrupt user queries. WAL disk usage should be monitored on primary as a safeguard.

**Override via environment variables:**

```yaml
POSTGRESQL_CONFIG_HOT_STANDBY_FEEDBACK: "on"
POSTGRESQL_CONFIG_MAX_STANDBY_STREAMING_DELAY: "-1"
POSTGRESQL_CONFIG_MAX_STANDBY_ARCHIVE_DELAY: "-1"
```

**Live fix without restart** — connect **directly to replica** (port 5432, not through PgBouncer):

```sql
ALTER SYSTEM SET hot_standby_feedback = on;
ALTER SYSTEM SET max_standby_streaming_delay = -1;
ALTER SYSTEM SET max_standby_archive_delay = -1;
SELECT pg_reload_conf();
```

**Verification:**

```sql
SHOW hot_standby_feedback;
SHOW max_standby_streaming_delay;
SHOW max_standby_archive_delay;
```

**Note:** These are **replica-only** settings. They have no effect on the primary.

### Invalid `synchronous_standby_names` Syntax

**Error in PostgreSQL logs:**
```
LOG:  invalid value for parameter "synchronous_standby_names": "ANY 2 (replica1,replica2)"
DETAIL:  syntax error at or near "-"
```

**Cause:** Replica names with hyphens require double-quoting in PostgreSQL.

**Solution:** Use names without hyphens, or ensure proper quoting:

```yaml
# Use underscores instead
REPLICATION_SYNCHRONOUS_REPLICAS: "replica_1,replica_2"
```

### Replica Names Don't Match

**Error in PostgreSQL logs:**
```
LOG:  could not accept standby connection
DETAIL:  replication source "unknown-replica" is not listed in synchronous_standby_names
```

**Cause:** The `application_name` sent by the replica doesn't match any name in `synchronous_standby_names`.

**Solution:** Ensure `REPLICATION_APPNAME` on replica matches exactly what's in `REPLICATION_SYNCHRONOUS_REPLICAS` on primary:

```bash
# Check replica's application_name
psql -h postgresql-primary -U postgres -c "SELECT application_name FROM pg_stat_replication;"
```

### Reload Script Fails

**Error:**
```
[ERROR] Failed to reload PostgreSQL configuration
```

**Solutions:**

1. **Check PostgreSQL is running:**
   ```bash
   pg_isready -h localhost -p 5432
   ```

2. **Check config syntax:**
   ```bash
   su - postgres -c "/usr/local/pgsql/bin/postgresql -D /usr/local/pgsql/data -C synchronous_standby_names"
   ```

3. **Check logs:**
   ```bash
   cat /usr/local/pgsql/log/*.log
   ```

## Performance Issues

### Slow Replication Causing Commit Delays

**Symptom:** Applications experience slow write operations.

**Cause:** Primary waiting for slow sync replicas.

**Solutions:**

1. **Check replica lag:**
   ```sql
   SELECT 
     application_name,
     pg_wal_lsn_diff(pg_current_wal_lsn(), replay_lsn) AS lag_bytes
   FROM pg_stat_replication;
   ```

2. **Temporarily switch to async:**
   ```bash
   pg-reload-sync-config.sh --sync-mode=false
   ```

3. **Improve network between primary and replicas**

### High Replication Lag

**Symptom:**
```sql
SELECT application_name, state, sync_lag FROM pg_stat_replication;
```
```
 application_name |   state   | sync_lag
-----------------+-----------+----------
 replica1        | streaming | 00:05:23
```

**Causes:**
- Slow replica hardware
- Network latency
- Heavy write load on primary

**Solutions:**
- Reduce write load
- Improve replica resources
- Consider async mode if lag is acceptable

## Kubernetes-Specific Issues

### Pod Name Changes After Restart

**Symptom:** Replicas can't connect after Kubernetes reschedules pods.

**Cause:** Pod hostname changed but `application_name` is still the old value.

**Solution:** Use fixed logical names instead of dynamic pod names:

```yaml
env:
  - name: REPLICATION_APPNAME
    value: "replica1"  # Fixed name, not $(HOSTNAME)
```

### Volume Persistence Issues

**Symptom:** Data doesn't persist between pod restarts.

**Cause:** Using `emptyDir` volumes instead of persistent volumes.

**Solution:** Use PersistentVolumeClaims:

```yaml
volumes:
  - name: postgres-data
    persistentVolumeClaim:
      claimName: postgres-pvc
```

## Getting Help

### Diagnostic Queries

Run these queries to diagnose replication issues:

```sql
-- 1. Replication status
SELECT * FROM pg_stat_replication;

-- 2. Replication slots
SELECT * FROM pg_replication_slots;

-- 3. Current WAL position
SELECT pg_current_wal_lsn();

-- 4. Replica lag
SELECT 
  application_name,
  pg_wal_lsn_diff(pg_current_wal_lsn(), replay_lsn) AS lag_bytes
FROM pg_stat_replication;

-- 5. Recovery status (run on replica)
SELECT pg_is_in_recovery();
```

### Log Analysis

```bash
# View PostgreSQL logs
docker compose logs postgresql-primary | grep -i replication

# View recent errors
docker compose logs --tail=100 postgresql-primary | grep -i error
```

## Related

- [Sync Modes](./sync_modes) - Understanding replication modes
- [Replica Names](./replica_names) - Application name configuration
- [Dynamic Configuration](./dynamic_config) - Runtime configuration changes

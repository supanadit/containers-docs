---
sidebar_position: 4
---

# Dynamic Configuration

You can change synchronous replication settings at runtime without restarting PostgreSQL using the `pg-reload-sync-config.sh` script.

## pg-reload-sync-config.sh

Located at `/usr/local/bin/pg-reload-sync-config.sh`, this script allows dynamic changes to:

- Enable/disable synchronous replication
- Change quorum count
- Add/remove replicas from the sync quorum

### Usage

```bash
pg-reload-sync-config.sh [OPTIONS]
```

#### Options

| Option | Description |
|--------|-------------|
| `--sync-mode MODE` | Enable/disable sync replication (`true` or `false`) |
| `--sync-count N` | Number of replicas required for sync (quorum mode) |
| `--sync-replicas NAMES` | Comma-separated list of replica application names |
| `--help` | Show help message |

### Examples

#### Disable Sync Replication (Async Mode)

```bash
pg-reload-sync-config.sh --sync-mode=false
```

This removes `synchronous_standby_names` from the configuration, allowing all replicas to operate asynchronously.

#### Enable Quorum Sync

```bash
pg-reload-sync-config.sh --sync-mode=true --sync-count=2 --sync-replicas="replica1,replica2"
```

This sets `synchronous_standby_names = 'ANY 2 (replica1, replica2)'`.

#### Change Sync Count

```bash
# Require only 1 sync replica instead of 2
pg-reload-sync-config.sh --sync-mode=true --sync-count=1 --sync-replicas="replica1,replica2"
```

#### Single Sync Mode

```bash
# Any replica can be sync (no specific names required)
pg-reload-sync-config.sh --sync-mode=true
```

This sets `synchronous_standby_names = '*'`.

## Docker Compose Usage

```bash
# Disable sync
docker compose exec postgresql-primary /usr/local/bin/pg-reload-sync-config.sh --sync-mode=false

# Enable quorum
docker compose exec postgresql-primary /usr/local/bin/pg-reload-sync-config.sh \
  --sync-mode=true --sync-count=2 --sync-replicas="replica1,replica2"
```

## Kubernetes Usage

### Exec in Pod

```bash
# Disable sync
kubectl exec postgresql-primary -- /usr/local/bin/pg-reload-sync-config.sh --sync-mode=false

# Enable quorum
kubectl exec postgresql-primary -- /usr/local/bin/pg-reload-sync-config.sh \
  --sync-mode=true --sync-count=2 --sync-replicas="replica1,replica2"
```

### Script Integration

You can integrate the script into your Kubernetes management workflows:

```yaml
# Example: ConfigMap with the script
apiVersion: v1
kind: ConfigMap
metadata:
  name: postgresql-scripts
data:
  reload-sync.sh: |
    #!/bin/bash
    /usr/local/bin/pg-reload-sync-config.sh "$@"
```

## Verification

After reloading, verify the configuration:

```sql
-- Check replication status
SELECT 
  client_addr,
  application_name,
  state,
  sync_state
FROM pg_stat_replication;
```

```
 client_addr | application_name |   state   | sync_state
-------------+------------------+-----------+------------
 10.0.1.5    | replica1         | streaming | sync
 10.0.1.6    | replica2         | streaming | sync
```

### Check Configuration

```sql
-- Show current synchronous_standby_names
SHOW synchronous_standby_names;
```

```
 synchronous_standby_names
--------------------------
 ANY 2 (replica1, replica2)
```

## Error Handling

The script **fails fast** on validation errors:

```bash
# Invalid sync count
pg-reload-sync-config.sh --sync-mode=true --sync-count=0
# [ERROR] Invalid REPLICATION_SYNCHRONOUS_COUNT: 0 (must be a positive integer)

# Invalid sync mode
pg-reload-sync-config.sh --sync-mode=invalid
# [ERROR] Invalid REPLICATION_SYNCHRONOUS_MODE: invalid (must be true or false)
```

On error, the script:
- Exits with code 1
- Does **not** modify the configuration
- Leaves the previous configuration intact

## Configuration Backup

Before applying changes, the script backs up the current configuration:

```
Backup location: /usr/local/pgsql/data/postgresql.conf.backup.YYYYMMDDHHMMSS
```

## Workflows

### Adding a Replica to Sync Quorum

1. Ensure the new replica is running and connected
2. Update the sync replicas list:
   ```bash
   kubectl exec postgresql-primary -- /usr/local/bin/pg-reload-sync-config.sh \
     --sync-mode=true --sync-count=3 --sync-replicas="replica1,replica2,replica3"
   ```
3. Verify:
   ```sql
   SELECT application_name, sync_state FROM pg_stat_replication;
   ```

### Removing a Replica from Sync Quorum

1. Update the sync replicas list (without the removed replica):
   ```bash
   kubectl exec postgresql-primary -- /usr/local/bin/pg-reload-sync-config.sh \
     --sync-mode=true --sync-count=1 --sync-replicas="replica1"
   ```
2. The replica continues replicating but no longer affects commit latency

### Switching to Async

```bash
kubectl exec postgresql-primary -- /usr/local/bin/pg-reload-sync-config.sh --sync-mode=false
```

## Related

- [Sync Modes](/docs/postgresql/native_ha/sync_modes) - Understanding replication modes
- [Replica Names](/docs/postgresql/native_ha/replica_names) - Understanding application names
- [Troubleshooting](/docs/postgresql/native_ha/troubleshooting) - Solving common issues

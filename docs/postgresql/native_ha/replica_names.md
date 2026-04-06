---
sidebar_position: 3
---

# Replica Application Names

Each replica must identify itself to the primary using an **application name**. This is critical for quorum synchronous replication where the primary needs to know exactly which replicas must acknowledge.

## How It Works

When a replica connects to the primary, it sends an `application_name` via `primary_conninfo`. The primary uses this name in `synchronous_standby_names` to identify which replicas are part of the sync quorum.

```
┌─────────────────┐
│   PostgreSQL    │  synchronous_standby_names = 'ANY 2 (replica1, replica2)'
│    Primary      │
└────────┬────────┘
         │ application_name sent during connection
         ▼
┌─────────────────┐         ┌─────────────────┐
│   replica1      │         │   replica2      │
│ (application_   │         │ (application_   │
│  name=replica1) │         │  name=replica2) │
└─────────────────┘         └─────────────────┘
```

## REPLICATION_APPNAME Environment Variable

On each replica, set `REPLICATION_APPNAME` to a unique identifier:

```yaml
# On replica1
environment:
  REPLICATION_APPNAME: replica1

# On replica2
environment:
  REPLICATION_APPNAME: replica2
```

If not set, the replica defaults to using the container's hostname.

## Naming Conventions

### Allowed Characters

PostgreSQL identifiers (like application names) can contain:

- Letters (a-z, A-Z)
- Digits (0-9)
- Underscores (_)
- Dollar signs ($)

### Special Characters (Hyphens, etc.)

Names with **special characters like hyphens** must be double-quoted in PostgreSQL:

```sql
-- This works (no special characters)
synchronous_standby_names = 'ANY 2 (replica1, replica2)'

-- This requires quoting
synchronous_standby_names = 'ANY 2 ("my-replica-1", "my-replica-2")'
```

:::warning
Using names with special characters adds complexity. We recommend using **underscores** instead of hyphens for replica names.
:::

### Good Naming Examples

| Name | Valid | Notes |
|------|-------|-------|
| `replica1` | ✓ | Simple, no special chars |
| `replica_1` | ✓ | Underscores are fine |
| `postgresql_replica1` | ✓ | Descriptive prefix |
| `my-replica-1` | ✗ | Hyphens require quoting |

## Kubernetes Considerations

In Kubernetes, pod names change on reschedule. Use a **fixed logical name** instead of the pod hostname:

### Using StatefulSet Ordinal

```yaml
# StatefulSet with stable pod names: postgresql-0, postgresql-1, etc.
env:
  - name: REPLICATION_APPNAME
    value: "postgresql-$(POD_NAME)"
```

### Using Downward API

```yaml
env:
  - name: POD_NAME
    valueFrom:
      fieldRef:
        fieldPath: metadata.name
  - name: REPLICATION_APPNAME
    value: "$(POD_NAME)"
```

### Fixed Logical Names

For maximum stability, use fixed names regardless of pod identity:

```yaml
# All replicas use fixed logical names
env:
  - name: REPLICATION_APPNAME
    value: "replica1"  # or "replica2", etc.
```

## Quorum Mode Requirements

For quorum synchronous replication, the replica names in `REPLICATION_SYNCHRONOUS_REPLICAS` must **exactly match** the `application_name` sent by replicas.

```yaml
# Primary configuration
environment:
  REPLICATION_SYNCHRONOUS_MODE: "true"
  REPLICATION_SYNCHRONOUS_COUNT: "2"
  REPLICATION_SYNCHRONOUS_REPLICAS: "replica1,replica2"

# Replica1 configuration
environment:
  REPLICATION_APPNAME: replica1

# Replica2 configuration
environment:
  REPLICATION_APPNAME: replica2
```

### Verification

On the primary, check that replicas are connecting with the correct names:

```sql
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

## Related

- [Sync Modes](./sync_modes) - Understanding single sync, quorum, and async modes
- [Dynamic Configuration](./dynamic_config) - Change configuration at runtime

---
sidebar_position: 2
---

# Synchronous Replication Modes

PostgreSQL supports three modes of replication that determine how the primary waits for replicas before committing transactions.

## Replication Modes Overview

| Mode | `REPLICATION_SYNCHRONOUS_MODE` | `REPLICATION_SYNCHRONOUS_COUNT` | `REPLICATION_SYNCHRONOUS_REPLICAS` | Use Case |
|------|--------------------------------|--------------------------------|-----------------------------------|----------|
| Single Sync | `true` | - | - | Basic HA, one sync replica |
| Quorum Sync | `true` | `N` | `replica1,replica2,...` | Critical data, multiple sync replicas |
| Async | `false` | - | - | Best performance, potential data loss |

## Single Sync Mode (Default)

The primary commits after waiting for **one** replica to acknowledge.

```yaml
environment:
  HA_MODE: native
  REPLICATION_ROLE: primary
  REPLICATION_SYNCHRONOUS_MODE: "true"
```

### Behavior

```
Primary: [writes] ──────► [sync replica]    (waits for 1 ack)
                   └─────► [potential replica] (standby)
```

- One replica becomes `sync` (primary waits for it)
- Other replicas become `potential` (will become sync if current sync fails)

### Verification

```sql
SELECT client_addr, state, sync_state FROM pg_stat_replication;
```

```
 client_addr |   state   | sync_state
-------------+-----------+------------
 10.0.1.5    | streaming | sync
 10.0.1.6    | streaming | potential
```

## Quorum Sync Mode

The primary commits after waiting for **multiple** replicas to acknowledge. Use this when data safety requires multiple replicas.

```yaml
environment:
  HA_MODE: native
  REPLICATION_ROLE: primary
  REPLICATION_SYNCHRONOUS_MODE: "true"
  REPLICATION_SYNCHRONOUS_COUNT: "2"
  REPLICATION_SYNCHRONOUS_REPLICAS: "replica1,replica2"
```

### Behavior

```
Primary: [writes] ──────► [replica1]  ┐
                   └─────► [replica2]  ├─► (waits for ALL N acks)
                   └─────► [replica3]  │
```

- All listed replicas must acknowledge before commit
- `sync_state` shows `sync` for all qualifying replicas

### Verification

```sql
SELECT client_addr, state, sync_state FROM pg_stat_replication;
```

```
 client_addr |   state   | sync_state
-------------+-----------+------------
 10.0.1.5    | streaming | sync
 10.0.1.6    | streaming | sync
 10.0.1.7    | streaming | potential
```

### Configuration Details

- `REPLICATION_SYNCHRONOUS_COUNT`: Number of replicas required (e.g., `2` means at least 2 must ack)
- `REPLICATION_SYNCHRONOUS_REPLICAS`: Comma-separated list of replica application names

## Async Mode

The primary does **not** wait for replicas. Best performance but potential data loss on failover.

```yaml
environment:
  HA_MODE: native
  REPLICATION_ROLE: primary
  REPLICATION_SYNCHRONOUS_MODE: "false"
```

### Behavior

```
Primary: [writes] ──────► [async replica]  (no waiting)
```

- Replicas may lag behind primary
- On failover, recent transactions may be lost

### Verification

```sql
SELECT client_addr, state, sync_state FROM pg_stat_replication;
```

```
 client_addr |   state   | sync_state
-------------+-----------+------------
 10.0.1.5    | streaming | async
 10.0.1.6    | streaming | async
```

## sync_state Values Explained

| Value | Meaning |
|-------|---------|
| `sync` | Synchronous - primary waits for this replica before committing |
| `potential` | Backup sync - will become `sync` if current sync fails |
| `async` | Async - primary does not wait for this replica |

## Choosing a Mode

### Single Sync

**Best for:** Basic high availability where you need at least one synchronous copy.

- Pros: Simple configuration, one sync replica
- Cons: Only one replica guaranteed to be in sync

### Quorum Sync

**Best for:** Critical data requiring multiple synchronous copies.

- Pros: Multiple replicas always in sync, better data safety
- Cons: Slower commits (must wait for multiple acks), more complex setup

### Async

**Best for:** Read scaling where data loss on failover is acceptable.

- Pros: Fastest performance, no waiting for replicas
- Cons: Potential data loss on failover

## Related

- [Replica Names](./replica_names) - Understanding application names for quorum mode
- [Dynamic Configuration](./dynamic_config) - Change sync settings at runtime

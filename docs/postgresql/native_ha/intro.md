---
sidebar_position: 1
---

# Native HA Introduction

Native HA provides high availability for PostgreSQL using **streaming replication** without requiring external tools like Patroni or etcd. It's a lightweight HA solution suitable for simpler deployments.

## How It Works

```
┌─────────────────┐      streaming       ┌─────────────────┐
│   PostgreSQL    │◄────────────────────│   PostgreSQL     │
│    Primary      │────────────────────►│    Replica 1    │
│   (master)      │      replication    │   (standby)     │
└─────────────────┘                      └─────────────────┘
                    ┌─────────────────┐
                    │   PostgreSQL     │
                    │    Replica 2    │
                    │   (standby)     │
                    └─────────────────┘
```

- **Primary**: Accepts writes and streams WAL to replicas
- **Replicas**: Receive WAL and replay it to stay in sync

## Native HA vs Patroni

| Feature | Native HA | Patroni |
|---------|-----------|---------|
| External dependencies | None | etcd, Consul, or ZooKeeper |
| Automatic failover | Manual | Automatic |
| Cluster coordination | None | Distributed |
| Complexity | Low | Medium-High |
| Use case | Simple HA, read scaling | Mission-critical HA |

## When to Use Native HA

- **Use Native HA when:**
  - You need simple primary-replica replication
  - You don't require automatic failover
  - You prefer to manage failover manually or via external scripts
  - You want a lightweight solution without external dependencies

- **Use Patroni when:**
  - You need automatic failover
  - You have mission-critical workloads
  - You need distributed consensus for cluster state
  - You want DCS-backed cluster management

## Environment Variables

Key environment variables for Native HA:

| Variable | Description | Required |
|----------|-------------|----------|
| `HA_MODE` | Set to `native` | Yes |
| `REPLICATION_ROLE` | `primary` or `replica` | Yes |
| `REPLICATION_USER` | Replication user | Yes |
| `REPLICATION_PASSWORD` | Replication password | Yes |
| `PRIMARY_HOST` | Primary hostname (replicas only) | Replica only |

## Default Replica Settings

Our container applies different defaults than vanilla PostgreSQL to ensure "works out of the box" behavior for read-heavy workloads.

### Why We Change PostgreSQL Defaults

PostgreSQL defaults are conservative (designed for DBAs who want full control). In production read-scaling scenarios, many users expect replica queries to just work reliably without manual tuning.

### Replica-Side Settings (Applied Automatically)

These are applied **only when `REPLICATION_ROLE=replica`** and do not affect the primary:

| Setting | PostgreSQL Default | Our Default | Reason |
|---------|-------------------|-------------|--------|
| `hot_standby_feedback` | `off` | `on` | Prevents VACUUM from removing rows the replica needs |
| `max_standby_streaming_delay` | `30s` | `-1` (infinite) | Never cancel long queries due to WAL replay conflicts |
| `max_standby_archive_delay` | `30s` | `-1` (infinite) | Never cancel for archived WAL conflicts |

### Overriding Defaults

All settings can be overridden via environment variables:

```yaml
# Example: Use stricter defaults instead of infinite
POSTGRESQL_CONFIG_MAX_STANDBY_STREAMING_DELAY: "60s"
POSTGRESQL_CONFIG_MAX_STANDBY_ARCHIVE_DELAY: "60s"

# Example: Disable hot_standby_feedback
POSTGRESQL_CONFIG_HOT_STANDBY_FEEDBACK: "off"
```

### WAL Disk Monitoring

When using `-1` (infinite) for standby delay settings, monitor the primary's WAL disk usage to ensure it doesn't grow unbounded if a replica falls behind or disconnects. Set up alerts on `pg_wal` directory size.

## Next Steps

- [Replication Modes](./sync_modes) - Choose between single sync, quorum sync, or async
- [Replica Names](./replica_names) - Understand application names for quorum mode
- [Dynamic Configuration](./dynamic_config) - Change sync settings at runtime
- [Troubleshooting](./troubleshooting) - Solve common issues

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

## Next Steps

- [Replication Modes](./sync_modes) - Choose between single sync, quorum sync, or async
- [Replica Names](./replica_names) - Understand application names for quorum mode
- [Dynamic Configuration](./dynamic_config) - Change sync settings at runtime
- [Troubleshooting](./troubleshooting) - Solve common issues

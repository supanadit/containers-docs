---
id: etcd-intro
title: ETCD
---

# ETCD

ETCD is a distributed key-value store that provides a reliable way to store data across a cluster of machines. It is often used for configuration management, service discovery, and coordination of distributed systems.

## Architecture

This container provides a production-ready ETCD setup with:

- **Go 1.24.x** runtime compiled from source
- **ETCD 3.6.7** with v3 API
- Environment-driven configuration
- TLS support out of the box
- Built-in backup/restore tools

## Quick Start

```yaml
services:
  etcd:
    image: ghcr.io/supanadit/containers/etcd:3.6.7-r4
    container_name: etcd
    environment:
      ETCD_NAME: etcd0
      ETCD_ADVERTISE_CLIENT_URLS: http://etcd:2379
      ETCD_INITIAL_CLUSTER: etcd0=http://etcd:2380
      ETCD_INITIAL_CLUSTER_STATE: new
      ETCD_INITIAL_CLUSTER_TOKEN: etcd-cluster
    ports:
      - "2379:2379"
      - "2380:2380"
    volumes:
      - etcd_data:/var/lib/etcd
    healthcheck:
      test: ["CMD", "etcdctl", "--endpoints=127.0.0.1:2379", "endpoint", "health"]
      interval: 5s
      timeout: 3s
      retries: 30

volumes:
  etcd_data:
```

## Deployment Scenarios

| Scenario | Use Case | Documentation |
|----------|----------|---------------|
| [Single Node](./cluster#single-node) | Development/testing | Basic single node |
| [3-Node Cluster](./cluster#multi-node-cluster) | Production | Multi-node cluster |
| [TLS Secured](./tls) | Production with security | TLS authentication |
| [Backup & Restore](./backup) | Data protection | Snapshot management |

## Common Operations

```bash
# Set a key
docker compose exec etcd etcdctl put mykey "myvalue"

# Get a key
docker compose exec etcd etcdctl get mykey

# List all keys
docker compose exec etcd etcdctl get / --prefix --keys-only

# Check cluster health
docker compose exec etcd etcdctl endpoint health

# Check cluster status
docker compose exec etcd etcdctl endpoint status
```

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `ETCD_NAME` | Node name | `etcd-node` |
| `ETCD_DATA_DIR` | Data directory | `/var/lib/etcd` |
| `ETCD_LISTEN_PEER_URLS` | Peer listening URLs | `http://0.0.0.0:2380` |
| `ETCD_LISTEN_CLIENT_URLS` | Client listening URLs | `http://0.0.0.0:2379` |
| `ETCD_ADVERTISE_CLIENT_URLS` | Advertised client URLs | Auto-detected |
| `ETCD_INITIAL_ADVERTISE_PEER_URLS` | Advertised peer URLs | Auto-detected |
| `ETCD_INITIAL_CLUSTER` | Initial cluster configuration | Auto-generated |
| `ETCD_INITIAL_CLUSTER_STATE` | Initial cluster state | `new` |
| `ETCD_INITIAL_CLUSTER_TOKEN` | Cluster token | `etcd-cluster` |

## Next Steps

- [Cluster Setup](./cluster) - Deploy multi-node ETCD clusters
- [TLS Configuration](./tls) - Secure ETCD with TLS certificates
- [Backup & Restore](./backup) - Backup and disaster recovery

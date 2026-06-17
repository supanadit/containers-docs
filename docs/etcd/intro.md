---
id: etcd-intro
slug: /etcd
title: ETCD
---

# ETCD

ETCD is a distributed key-value store that provides a reliable way to store data across a cluster of machines. It is often used for configuration management, service discovery, and coordination of distributed systems.

## Architecture

This container provides a production-ready ETCD setup with:

- **Go 1.24.12** compiled from source
- **ETCD 3.6.7** with v3 API
- **Multi-stage build** for minimal runtime image (`debian:bookworm-slim`)
- **Environment-driven configuration** for all settings
- **TLS support** for secure communication
- **Automatic data directory creation** with proper permissions (700)

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
      - etcd_data:/opt/containers/data
    healthcheck:
      test: ["CMD", "etcdctl", "--endpoints=127.0.0.1:2379", "endpoint", "health"]
      interval: 5s
      timeout: 3s
      retries: 30

volumes:
  etcd_data:
```

## Built-in Settings

The following settings are hardcoded in the entrypoint for optimal performance:

| Setting | Value | Description |
|---------|-------|-------------|
| `--heartbeat-interval` | 1000 | Heartbeat interval in milliseconds |
| `--election-timeout` | 5000 | Election timeout in milliseconds |
| `--snapshot-count` | 5000 | Number of operations before snapshot |
| `--auto-compaction-retention` | 1 | Auto compaction retention in hours |
| `--max-request-bytes` | 10485760 | Maximum request size (10MB) |

## Deployment Scenarios

| Scenario | Use Case | Documentation |
|----------|----------|---------------|
| [Single Node](/docs/etcd/etcd-cluster#single-node) | Development/testing | Basic single node |
| [3-Node Cluster](/docs/etcd/etcd-cluster#multi-node-cluster) | Production | Multi-node cluster |
| [TLS Secured](/docs/etcd/etcd-tls) | Production with security | TLS authentication |

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
| `ETCD_DATA_DIR` | Data directory | `/opt/containers/data` |
| `ETCD_LISTEN_PEER_URLS` | Peer listening URLs | `http://0.0.0.0:2380` |
| `ETCD_LISTEN_CLIENT_URLS` | Client listening URLs | `http://0.0.0.0:2379` |
| `ETCD_ADVERTISE_CLIENT_URLS` | Advertised client URLs | Auto-detected from container IP |
| `ETCD_INITIAL_ADVERTISE_PEER_URLS` | Advertised peer URLs | Auto-detected from container IP |
| `ETCD_INITIAL_CLUSTER` | Initial cluster configuration | Auto-generated from name and IP |
| `ETCD_INITIAL_CLUSTER_STATE` | Initial cluster state | `new` |
| `ETCD_INITIAL_CLUSTER_TOKEN` | Cluster token | `etcd-cluster` |

## TLS Environment Variables

| Variable | Description |
|----------|-------------|
| `ETCD_CERT_FILE` | TLS certificate file for client connections |
| `ETCD_KEY_FILE` | TLS key file for client connections |
| `ETCD_CLIENT_CERT_AUTH` | Enable client certificate authentication (`true`/`false`) |
| `ETCD_TRUSTED_CA_FILE` | Trusted CA certificate file |
| `ETCD_PEER_CERT_FILE` | TLS certificate file for peer connections |
| `ETCD_PEER_KEY_FILE` | TLS key file for peer connections |
| `ETCD_PEER_CLIENT_CERT_AUTH` | Enable peer certificate authentication (`true`/`false`) |
| `ETCD_PEER_TRUSTED_CA_FILE` | Trusted CA file for peers |

## Next Steps
- [Cluster Setup](/docs/etcd/etcd-cluster) - Deploy multi-node ETCD clusters

- [TLS Configuration](/docs/etcd/etcd-tls) - Secure ETCD with TLS certificates

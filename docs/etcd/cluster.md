---
id: etcd-cluster
title: Cluster Setup
---

# Cluster Setup

## Single Node

For local development and testing:

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

## Multi-Node Cluster

A 3-node ETCD cluster for production workloads:

```yaml
services:
  etcd-1:
    image: ghcr.io/supanadit/containers/etcd:3.6.7-r4
    container_name: etcd-1
    environment:
      ETCD_NAME: etcd-1
      ETCD_ADVERTISE_CLIENT_URLS: http://etcd-1:2379
      ETCD_INITIAL_CLUSTER: etcd-1=http://etcd-1:2380,etcd-2=http://etcd-2:2380,etcd-3=http://etcd-3:2380
      ETCD_INITIAL_CLUSTER_STATE: new
      ETCD_INITIAL_CLUSTER_TOKEN: etcd-cluster
    ports:
      - "2379:2379"
      - "2380:2380"
    volumes:
      - etcd_1_data:/var/lib/etcd
    healthcheck:
      test: ["CMD", "etcdctl", "--endpoints=127.0.0.1:2379", "endpoint", "health"]
      interval: 5s
      timeout: 3s
      retries: 30

  etcd-2:
    image: ghcr.io/supanadit/containers/etcd:3.6.7-r4
    container_name: etcd-2
    environment:
      ETCD_NAME: etcd-2
      ETCD_ADVERTISE_CLIENT_URLS: http://etcd-2:2379
      ETCD_INITIAL_CLUSTER: etcd-1=http://etcd-1:2380,etcd-2=http://etcd-2:2380,etcd-3=http://etcd-3:2380
      ETCD_INITIAL_CLUSTER_STATE: new
      ETCD_INITIAL_CLUSTER_TOKEN: etcd-cluster
    ports:
      - "2380:2380"
    volumes:
      - etcd_2_data:/var/lib/etcd
    healthcheck:
      test: ["CMD", "etcdctl", "--endpoints=127.0.0.1:2379", "endpoint", "health"]
      interval: 5s
      timeout: 3s
      retries: 30

  etcd-3:
    image: ghcr.io/supanadit/containers/etcd:3.6.7-r4
    container_name: etcd-3
    environment:
      ETCD_NAME: etcd-3
      ETCD_ADVERTISE_CLIENT_URLS: http://etcd-3:2379
      ETCD_INITIAL_CLUSTER: etcd-1=http://etcd-1:2380,etcd-2=http://etcd-2:2380,etcd-3=http://etcd-3:2380
      ETCD_INITIAL_CLUSTER_STATE: new
      ETCD_INITIAL_CLUSTER_TOKEN: etcd-cluster
    ports:
      - "2381:2380"
    volumes:
      - etcd_3_data:/var/lib/etcd
    healthcheck:
      test: ["CMD", "etcdctl", "--endpoints=127.0.0.1:2379", "endpoint", "health"]
      interval: 5s
      timeout: 3s
      retries: 30

volumes:
  etcd_1_data:
  etcd_2_data:
  etcd_3_data:

networks:
  default:
    name: etcd-cluster-network
    driver: bridge
```

## Cluster Operations

```bash
# Check cluster health
docker compose exec etcd-1 etcdctl endpoint health

# Check cluster members
docker compose exec etcd-1 etcdctl member list

# Check cluster status
docker compose exec etcd-1 etcdctl endpoint status

# View cluster members
docker compose exec etcd-1 etcdctl --endpoints=etcd-1:2379,etcd-2:2379,etcd-3:2379 endpoint status
```

## Important Notes

### Cluster Token

The `ETCD_INITIAL_CLUSTER_TOKEN` must be the same for all nodes when forming a new cluster. To join an existing cluster, change `ETCD_INITIAL_CLUSTER_STATE` to `existing`.

### Advertised URLs

The `ETCD_ADVERTISE_CLIENT_URLS` and `ETCD_INITIAL_ADVERTISE_PEER_URLS` must match the container hostname for inter-container communication. Use the container name as the hostname:

```yaml
ETCD_ADVERTISE_CLIENT_URLS: http://etcd-1:2379
ETCD_INITIAL_ADVERTISE_PEER_URLS: http://etcd-1:2380
```

### Data Directory

The data directory (`/var/lib/etcd`) stores ETCD's WAL and snapshot files. Ensure:
- Adequate disk space
- Proper permissions (700)
- Regular backups

---
id: etcd-intro-doc
slug: /etcd
title: ETCD
---

# ETCD

ETCD is a distributed key-value store that provides a reliable way to store data across a cluster of machines. It is often used for configuration management, service discovery, and coordination of distributed systems.

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
      test:
        ["CMD", "etcdctl", "--endpoints=127.0.0.1:2379", "endpoint", "health"]
      interval: 5s
      timeout: 3s
      retries: 30

volumes:
  etcd_data:
```

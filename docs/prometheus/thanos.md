---
id: thanos
title: Thanos Integration
---

# Thanos Integration

Thanos provides long-term storage, global view, and downsampling for Prometheus. See the [Thanos documentation](/docs/thanos/thanos-intro) for complete reference.

## Architecture

Thanos components work together to provide a complete observability stack:

```
┌─────────────────┐
│   Prometheus   │────────┐
└────────┬────────┘        │
         │ (sidecar)       │ upload
         ▼                 ▼
┌─────────────────┐  ┌─────────────────┐
│  Thanos Sidecar │─▶│   Object Store  │
└────────┬────────┘  └─────────────────┘
         │                   ▲
         │ store             │
         ▼                   │
┌─────────────────┐  ┌─────────────────┐
│  Thanos Store   │─▶│ Thanos Compact │
└─────────────────┘  └─────────────────┘
         │
         ▼
┌─────────────────┐  ┌─────────────────┐
│  Thanos Query   │◀─│Query Frontend   │
└─────────────────┘  └─────────────────┘
```

## Quick Start

Deploy Prometheus with Thanos sidecar for long-term storage:

```yaml
services:
  prometheus:
    image: ghcr.io/supanadit/containers/prometheus:3.8.1-r2
    environment:
      PROMETHEUS_ENABLE_WEB_LIFECYCLE: "true"
      PROMETHEUS_STORAGE_TSDB_MIN_BLOCK_DURATION: "2h"
      PROMETHEUS_STORAGE_TSDB_MAX_BLOCK_DURATION: "2h"
    volumes:
      - prometheus_data:/opt/prometheus/data
      - ./config/prometheus.yml:/etc/prometheus/prometheus.yml

  thanos-sidecar:
    image: ghcr.io/supanadit/containers/thanos:0.40.1-r4
    environment:
      THANOS_COMPONENT: sidecar
      THANOS_SIDECAR_PROMETHEUS_URL: "http://prometheus:9090"
      THANOS_S3_BUCKET: "thanos"
      THANOS_S3_ENDPOINT: "minio:9000"
      THANOS_S3_ACCESS_KEY: "admin"
      THANOS_S3_SECRET_KEY: "password"
      THANOS_S3_INSECURE: "true"
    volumes:
      - prometheus_data:/opt/thanos/data

  thanos-query:
    image: ghcr.io/supanadit/containers/thanos:0.40.1-r4
    environment:
      THANOS_COMPONENT: query
      THANOS_QUERY_STORE_ADDRESSES: "thanos-sidecar:10901"
    ports:
      - "10902:10902"

volumes:
  prometheus_data:
```

## Key Configuration

### Web Lifecycle

Enable Prometheus web lifecycle for Thanos to reload configuration:

```yaml
environment:
  PROMETHEUS_ENABLE_WEB_LIFECYCLE: "true"
```

### Block Duration

Set matching min/max block durations for proper block uploads:

```yaml
environment:
  PROMETHEUS_STORAGE_TSDB_MIN_BLOCK_DURATION: "2h"
  PROMETHEUS_STORAGE_TSDB_MAX_BLOCK_DURATION: "2h"
```

## Data Directory Mount

Mount Prometheus data directory to both containers:

```yaml
# Prometheus
volumes:
  - prometheus_data:/opt/prometheus/data

# Thanos sidecar
volumes:
  - prometheus_data:/opt/thanos/data
```

## Next Steps

- [Thanos Documentation](/docs/thanos/thanos-intro) - Complete Thanos reference
- [Thanos Components](/docs/thanos/thanos-intro#components) - All available components
- [S3 Configuration](/docs/thanos/thanos-intro#s3-object-store-configuration) - Object store setup

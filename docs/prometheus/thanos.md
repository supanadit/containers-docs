---
id: prometheus-thanos
title: Thanos Integration
---

# Thanos Integration

Thanos provides long-term storage, global view, and downsampling for Prometheus. This container supports multiple Thanos components.

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

## Docker Compose Example

A complete production stack with Prometheus, Thanos sidecar, store, compact, query, and query-frontend:

```yaml
services:
  prometheus:
    image: ghcr.io/supanadit/containers/prometheus:3.8.1-r2
    container_name: prometheus
    environment:
      PROMETHEUS_PORT: 9090
      PROMETHEUS_ENABLE_NATIVE_HISTOGRAM: "true"
      PROMETHEUS_ENABLE_EXEMPLAR_STORAGE: "true"
      PROMETHEUS_ENABLE_WEB_LIFECYCLE: "true"
      PROMETHEUS_STORAGE_TSDB_MIN_BLOCK_DURATION: "2h"
      PROMETHEUS_STORAGE_TSDB_MAX_BLOCK_DURATION: "2h"
    ports:
      - "9090:9090"
    volumes:
      - prometheus_data:/opt/prometheus/data
      - ./config/prometheus.yml:/etc/prometheus/prometheus.yml

  thanos-sidecar:
    image: ghcr.io/supanadit/containers/thanos:0.37.2-r1
    container_name: thanos-sidecar
    environment:
      THANOS_HTTP_ADDRESS: ":10902"
      THANOS_GRPC_ADDRESS: ":10901"
      THANOS_COMPONENT: "sidecar"
      THANOS_SIDECAR_PROMETHEUS_URL: "http://prometheus:9090"
      THANOS_S3_BUCKET: "thanos"
      THANOS_S3_ENDPOINT: "minio:9000"
      THANOS_S3_ACCESS_KEY: "superadmin"
      THANOS_S3_SECRET_KEY: "supersecretpassword"
      THANOS_S3_INSECURE: "true"
    ports:
      - "10902:10902"
      - "10901:10901"
    volumes:
      - prometheus_data:/opt/thanos/data
    depends_on:
      - prometheus

  thanos-store:
    image: ghcr.io/supanadit/containers/thanos:0.37.2-r1
    container_name: thanos-store
    environment:
      THANOS_HTTP_ADDRESS: ":10905"
      THANOS_GRPC_ADDRESS: ":10904"
      THANOS_COMPONENT: "store"
      THANOS_S3_BUCKET: "thanos"
      THANOS_S3_ENDPOINT: "minio:9000"
      THANOS_S3_ACCESS_KEY: "superadmin"
      THANOS_S3_SECRET_KEY: "supersecretpassword"
      THANOS_S3_INSECURE: "true"
      THANOS_STORE_INDEX_CACHE_CONFIG: |
        type: IN-MEMORY
        config:
          max_size: "2GB"
          max_item_size: "125MB"
    ports:
      - "10905:10905"
      - "10904:10904"
    volumes:
      - thanos_store_data:/opt/thanos/data

  thanos-compact:
    image: ghcr.io/supanadit/containers/thanos:0.37.2-r1
    container_name: thanos-compact
    environment:
      THANOS_COMPONENT: "compact"
      THANOS_S3_BUCKET: "thanos"
      THANOS_S3_ENDPOINT: "minio:9000"
      THANOS_S3_ACCESS_KEY: "superadmin"
      THANOS_S3_SECRET_KEY: "supersecretpassword"
      THANOS_S3_INSECURE: "true"
      THANOS_COMPACT_RETENTION_RAW: "90d"
      THANOS_COMPACT_RETENTION_5M: "1y"
      THANOS_COMPACT_RETENTION_1H: "3y"
    volumes:
      - thanos_compact_data:/opt/thanos/data

  thanos-query:
    image: ghcr.io/supanadit/containers/thanos:0.37.2-r1
    container_name: thanos-query
    environment:
      THANOS_HTTP_ADDRESS: ":10907"
      THANOS_GRPC_ADDRESS: ":10906"
      THANOS_COMPONENT: "query"
      THANOS_QUERY_STORE_ADDRESSES: "thanos-store:10904,thanos-sidecar:10901"
    ports:
      - "10907:10907"
      - "10906:10906"

  thanos-query-frontend:
    image: ghcr.io/supanadit/containers/thanos:0.37.2-r1
    container_name: thanos-query-frontend
    environment:
      THANOS_HTTP_ADDRESS: ":10909"
      THANOS_COMPONENT: "query-frontend"
      THANOS_QUERY_FRONTEND_DOWNSTREAM_URL: "http://thanos-query:10907"
      THANOS_QUERY_RANGE_SPLIT_INTERVAL: "24h"
      THANOS_QUERY_RANGE_MAX_RETRIES: "5"
    ports:
      - "10909:10909"

volumes:
  prometheus_data:
  thanos_store_data:
  thanos_compact_data:
```

## Thanos Environment Variables

### Common Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `THANOS_COMPONENT` | Thanos component to run | `query` |
| `THANOS_HTTP_ADDRESS` | HTTP listen address | `0.0.0.0:10902` |
| `THANOS_GRPC_ADDRESS` | gRPC listen address | `0.0.0.0:10901` |
| `THANOS_DATA_DIR` | Data directory | `/opt/thanos/data` |

### S3 Configuration

| Variable | Description |
|----------|-------------|
| `THANOS_S3_BUCKET` | S3 bucket name |
| `THANOS_S3_ENDPOINT` | S3 endpoint |
| `THANOS_S3_ACCESS_KEY` | Access key |
| `THANOS_S3_SECRET_KEY` | Secret key |
| `THANOS_S3_INSECURE` | Use insecure HTTP |
| `THANOS_S3_SIGNATURE_V2` | Use AWS Signature V2 |

### Component-Specific Variables

**Sidecar:**
- `THANOS_SIDECAR_PROMETHEUS_URL` - Prometheus URL
- `THANOS_SIDECAR_SHIPPER_UPLOAD_COMPACTED` - Upload compacted blocks

**Store:**
- `THANOS_STORE_INDEX_CACHE_CONFIG` - Index cache config

**Query:**
- `THANOS_QUERY_STORE_ADDRESSES` - Comma-separated store addresses
- `THANOS_REPLICA_LABEL` - Replica label for deduplication

**Query Frontend:**
- `THANOS_QUERY_FRONTEND_DOWNSTREAM_URL` - Downstream query URL

**Compact:**
- `THANOS_COMPACT_RETENTION_RAW` - Raw block retention
- `THANOS_COMPACT_RETENTION_5M` - 5m downsampled block retention
- `THANOS_COMPACT_RETENTION_1H` - 1h downsampled block retention

## Data Directory Mount

The Prometheus data directory must be mounted to both Prometheus and Thanos sidecar containers with **the same path**:

```yaml
# Prometheus
volumes:
  - prometheus_data:/opt/prometheus/data

# Thanos sidecar
volumes:
  - prometheus_data:/opt/thanos/data
```

## Important Notes

### Web Lifecycle

Enable `--web.enable-lifecycle` on Prometheus for Thanos to reload configuration:

```yaml
environment:
  PROMETHEUS_ENABLE_WEB_LIFECYCLE: "true"
```

### Block Duration

For Thanos to properly upload blocks, set matching min/max block durations:

```yaml
environment:
  PROMETHEUS_STORAGE_TSDB_MIN_BLOCK_DURATION: "2h"
  PROMETHEUS_STORAGE_TSDB_MAX_BLOCK_DURATION: "2h"
```

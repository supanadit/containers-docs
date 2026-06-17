---
id: thanos-intro
title: Thanos
---

# Thanos

Thanos is a set of components that extend Prometheus capabilities for long-term storage, high availability, and global view of metrics.

## Architecture

This container provides a production-ready Thanos setup with:

- **Thanos 0.40.1** latest stable release
- **Multi-component support** (query, sidecar, store, compact, receive, query-frontend, rule)
- **Environment-driven configuration** for all settings
- **S3 object store support** for durable storage
- **Multi-tenancy** support via Receiver

## Quick Start

```yaml
services:
  thanos-query:
    image: ghcr.io/supanadit/containers/thanos:0.40.1-r4
    container_name: thanos-query
    environment:
      THANOS_COMPONENT: query
      THANOS_HTTP_ADDRESS: ":10902"
      THANOS_GRPC_ADDRESS: ":10901"
      THANOS_QUERY_STORE_ADDRESSES: "thanos-store:10904"
    ports:
      - "10902:10902"
      - "10901:10901"
```

## Components

Thanos can run in multiple modes:

| Component | Description |
|-----------|-------------|
| `query` | Prometheus query engine with deduplication |
| `sidecar` | Prometheus sidecar for block upload |
| `store` | Store API for querying stored blocks |
| `compact` | Block compactor with retention |
| `receive` | Receiver for remote write (multi-tenant) |
| `query-frontend` | Query frontend with caching |
| `rule` | Recording rules and alerts |

## Environment Variables

### Common Configuration

| Variable | Description | Default |
|----------|-------------|---------|
| `THANOS_COMPONENT` | Component to run | `query` |
| `THANOS_HTTP_ADDRESS` | HTTP listen address | `0.0.0.0:10902` |
| `THANOS_GRPC_ADDRESS` | gRPC listen address | `0.0.0.0:10901` |
| `THANOS_DATA_DIR` | Data directory | `/opt/containers/data` |

### Query Configuration

| Variable | Description |
|----------|-------------|
| `THANOS_QUERY_STORE_ADDRESSES` | Comma-separated store addresses |
| `THANOS_REPLICA_LABEL` | Replica label for deduplication |

### Sidecar Configuration

| Variable | Description |
|----------|-------------|
| `THANOS_SIDECAR_PROMETHEUS_URL` | Prometheus URL |
| `THANOS_SIDECAR_SHIPPER_UPLOAD_COMPACTED` | Upload compacted blocks |

### Store Configuration

| Variable | Description |
|----------|-------------|
| `THANOS_STORE_INDEX_CACHE_CONFIG` | Index cache configuration (YAML) |
| `THANOS_STORE_CACHING_BUCKET_CONFIG` | Caching bucket configuration (YAML) |

### Query Frontend Configuration

| Variable | Description |
|----------|-------------|
| `THANOS_QUERY_FRONTEND_DOWNSTREAM_URL` | Downstream query URL |
| `THANOS_QUERY_RANGE_SPLIT_INTERVAL` | Range query split interval |
| `THANOS_QUERY_RANGE_MAX_RETRIES` | Maximum retries per request |
| `THANOS_LABELS_SPLIT_INTERVAL` | Labels split interval |

### Compact Configuration

| Variable | Description |
|----------|-------------|
| `THANOS_COMPACT_DOWNSAMPLING_DISABLE` | Disable downsampling |
| `THANOS_COMPACT_RETENTION_RAW` | Raw block retention |
| `THANOS_COMPACT_RETENTION_5M` | 5m downsampled block retention |
| `THANOS_COMPACT_RETENTION_1H` | 1h downsampled block retention |
| `THANOS_COMPACT_CONSISTENCY_DELAY` | Consistency delay |
| `THANOS_COMPACT_COMPACTION_CONCURRENCY` | Compaction concurrency |

### Receive Configuration

| Variable | Description |
|----------|-------------|
| `THANOS_RECEIVE_REPLICATION_FACTOR` | Replication factor |
| `THANOS_RECEIVE_LOCAL_ENDPOINT` | Local endpoint |
| `THANOS_RECEIVE_HASHRING_FILE` | Hashring file path |

### S3 Object Store Configuration

| Variable | Description |
|----------|-------------|
| `THANOS_S3_BUCKET` | S3 bucket name |
| `THANOS_S3_ENDPOINT` | S3 endpoint URL |
| `THANOS_S3_ACCESS_KEY` | S3 access key |
| `THANOS_S3_SECRET_KEY` | S3 secret key |
| `THANOS_S3_INSECURE` | Use HTTP instead of HTTPS |
| `THANOS_S3_SIGNATURE_V2` | Use AWS Signature V2 |
| `THANOS_S3_CA_FILE` | CA certificate file |
| `THANOS_S3_CERT_FILE` | Client certificate file |
| `THANOS_S3_KEY_FILE` | Client key file |
| `THANOS_S3_INSECURE_SKIP_VERIFY` | Skip TLS verification |

## Deployment Scenarios

| Scenario | Use Case | Documentation |
|----------|----------|---------------|
| [Basic Query](#query-configuration) | Query metrics from Prometheus | Query component |
| [With Prometheus](/docs/prometheus/thanos) | Long-term storage | Prometheus + Thanos |
| [Multi-tenant](#receive-configuration) | Receive remote writes | Receiver configuration |

## Example: Full Stack with Prometheus

```yaml
services:
  prometheus:
    image: ghcr.io/supanadit/containers/prometheus:3.8.1-r2
    environment:
      PROMETHEUS_ENABLE_WEB_LIFECYCLE: "true"
      PROMETHEUS_STORAGE_TSDB_MIN_BLOCK_DURATION: "2h"
      PROMETHEUS_STORAGE_TSDB_MAX_BLOCK_DURATION: "2h"
    volumes:
      - prometheus_data:/opt/containers/data
      - ./prometheus.yml:/etc/prometheus/prometheus.yml

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
      - prometheus_data:/opt/containers/data

  thanos-store:
    image: ghcr.io/supanadit/containers/thanos:0.40.1-r4
    environment:
      THANOS_COMPONENT: store
      THANOS_S3_BUCKET: "thanos"
      THANOS_S3_ENDPOINT: "minio:9000"
      THANOS_S3_ACCESS_KEY: "admin"
      THANOS_S3_SECRET_KEY: "password"
      THANOS_S3_INSECURE: "true"

  thanos-query:
    image: ghcr.io/supanadit/containers/thanos:0.40.1-r4
    environment:
      THANOS_COMPONENT: query
      THANOS_QUERY_STORE_ADDRESSES: "thanos-store:10901,thanos-sidecar:10901"
    ports:
      - "10902:10902"

volumes:
  prometheus_data:
```

## Important Notes

### Data Directory

Different components require different volume mounts:

- **Sidecar**: Mount Prometheus data to `/opt/containers/data`
- **Store**: Use separate volume at `/opt/containers/data`
- **Compact**: Use separate volume at `/opt/containers/data`
- **Receive**: Use volume at `/opt/containers/data`

### S3 Compatibility

Tested compatible with:
- MinIO
- AWS S3
- Google Cloud Storage
- Azure Blob Storage (via custom endpoint)

### Prometheus Web Lifecycle

When using Thanos sidecar, enable Prometheus web lifecycle for config reloading:

```yaml
environment:
  PROMETHEUS_ENABLE_WEB_LIFECYCLE: "true"
```

## Next Steps

- [Prometheus Integration](/docs/prometheus/thanos) - Deploy Thanos with Prometheus
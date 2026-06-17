---
id: prometheus-features
title: Features
---

# Features

Prometheus includes various experimental and production features that can be enabled via environment variables.

## Enabling Features

All feature flags are boolean (`true`/`false`):

```yaml
environment:
  PROMETHEUS_ENABLE_NATIVE_HISTOGRAM: "true"
  PROMETHEUS_ENABLE_EXEMPLAR_STORAGE: "true"
  # ... other features
```

## Native Histogram

Native histograms provide higher fidelity for histogram metrics:

```yaml
environment:
  PROMETHEUS_ENABLE_NATIVE_HISTOGRAM: "true"
```

This enables the `/api/v2/format` endpoint and native histogram support in the scrape configuration.

## Exemplar Storage

Store traces alongside metrics for correlation:

```yaml
environment:
  PROMETHEUS_ENABLE_EXEMPLAR_STORAGE: "true"
```

Exemplars can be attached to histogram metrics using the `trace_id` label.

## Memory Snapshot on Shutdown

Save in-memory WAL to disk on container shutdown:

```yaml
environment:
  PROMETHEUS_ENABLE_MEMORY_SNAPSHOT_ON_SHUTDOWN: "true"
```

This helps preserve data that hasn't been compacted yet.

## Web Lifecycle

Enable configuration reload and shutdown endpoints:

```yaml
environment:
  PROMETHEUS_ENABLE_WEB_LIFECYCLE: "true"
```

This enables:
- `POST /-/reload` - Reload configuration
- `GET /-/quit` - Shutdown gracefully

## Remote Write Receiver

Receive metrics from remote Prometheus instances:

```yaml
environment:
  PROMETHEUS_ENABLE_WEB_REMOTE_WRITE_RECEIVER: "true"
```

## OTLP Receiver

Receive OpenTelemetry metrics:

```yaml
environment:
  PROMETHEUS_ENABLE_WEB_OTLP_RECEIVER: "true"
```

## Additional Experimental Features

| Variable | Feature | Description |
|----------|---------|-------------|
| `PROMETHEUS_ENABLE_EXTRA_SCRAPE_METRICS` | Extra scrape metrics | Include additional scrape timing metrics |
| `PROMETHEUS_ENABLE_PER_STEP_STATS` | Per-step stats | Enable per-step statistics in PromQL |
| `PROMETHEUS_ENABLE_PROMQL_FUNCTIONS` | Experimental PromQL functions | Enable experimental functions |
| `PROMETHEUS_ENABLE_CREATED_TIMESTAMPS_ZERO_INJECTION` | Zero injection for created timestamps | Inject zero for missing created timestamps |
| `PROMETHEUS_ENABLE_CONCURRENT_RULE_EVAL` | Concurrent rule evaluation | Evaluate rules concurrently |
| `PROMETHEUS_ENABLE_OLD_UI` | Old UI | Enable the classic Prometheus UI |
| `PROMETHEUS_ENABLE_METADATA_WAL_RECORDS` | Metadata WAL records | Store metadata in WAL |
| `PROMETHEUS_ENABLE_DELAYED_COMPACTION` | Delayed compaction | Delay compaction operations |
| `PROMETHEUS_ENABLE_PROMQL_DELAYED_NAME_REMOVAL` | Delayed name removal | Delay metric name removal in PromQL |
| `PROMETHEUS_ENABLE_AUTO_RELOAD_CONFIG` | Auto reload config | Automatically reload config on changes |
| `PROMETHEUS_ENABLE_OLTP_DELTA_CONVERSION` | OTLP delta conversion | Convert OTLP metrics with delta temporality |
| `PROMETHEUS_ENABLE_PROMQL_DURATION_EXPR` | Duration expressions | Enable duration expressions in PromQL |
| `PROMETHEUS_ENABLE_OLTP_NATIVE_DELTA` | OTLP native delta | Native delta ingestion for OTLP |
| `PROMETHEUS_ENABLE_TYPE_AND_UNIT_LABELS` | Type and unit labels | Add type and unit labels to metrics |
| `PROMETHEUS_ENABLE_USE_UNCACHED_IO` | Uncached I/O | Use uncached I/O for storage |

## Recommended Production Configuration

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
      PROMETHEUS_STORAGE_TSDB_RETENTION_TIME: "30d"
    ports:
      - "9090:9090"
    volumes:
      - prometheus_data:/opt/containers/data
      - ./config/prometheus.yml:/opt/containers/config/prometheus.yml
```

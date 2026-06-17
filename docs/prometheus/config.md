---
id: prometheus-config
title: Configuration
---

# Configuration

## Environment Variables

### Basic Configuration

| Variable | Description | Default |
|----------|-------------|---------|
| `PROMETHEUS_PORT` | Web UI listen port | `9090` |
| `PROMETHEUS_CONFIG_FILE` | Config file path | `/opt/containers/config/prometheus.yml` |
| `PROMETHEUS_DATA_DIR` | TSDB data directory | `/opt/containers/data` |
| `PROMETHEUS_WEB_CONFIG_FILE` | TLS web config file | - |

### Storage Configuration

| Variable | Description | Default |
|----------|-------------|---------|
| `PROMETHEUS_STORAGE_TSDB_MIN_BLOCK_DURATION` | Minimum TSDB block duration | - |
| `PROMETHEUS_STORAGE_TSDB_MAX_BLOCK_DURATION` | Maximum TSDB block duration | - |
| `PROMETHEUS_STORAGE_TSDB_RETENTION_TIME` | Data retention time | - |
| `PROMETHEUS_STORAGE_TSDB_RETENTION_SIZE` | Data retention size | - |

## Basic Prometheus Configuration

```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 15s
  external_labels:
    monitor: 'my-monitor'

scrape_configs:
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']

  - job_name: 'node-exporter'
    static_configs:
      - targets: ['node-exporter:9100']
```

## Example: Production Configuration

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
      - prometheus_data:/opt/containers/data
      - ./config/prometheus.yml:/opt/containers/config/prometheus.yml
    healthcheck:
      test: ["CMD-SHELL", "wget -q --spider http://localhost:9090/-/healthy || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  prometheus_data:
```

## Scrape Configuration Examples

### Static Targets

```yaml
scrape_configs:
  - job_name: 'static-targets'
    static_configs:
      - targets:
          - 'node-exporter:9100'
          - 'cadvisor:8080'
        labels:
          environment: 'production'
```

### Service Discovery

```yaml
scrape_configs:
  - job_name: 'kubernetes-pods'
    kubernetes_sd_configs:
      - role: pod
    relabel_configs:
      - source_labels: [__meta_kubernetes_pod_annotation_prometheus_io_scrape]
        action: keep
        regex: true
```

### Federation

```yaml
scrape_configs:
  - job_name: 'federate'
    honor_labels: true
    metrics_path: '/federate'
    params:
      'match[]':
        - '{job="prometheus"}'
    static_configs:
      - targets:
          - 'primary-prometheus:9090'
```

## Alertmanager Configuration

```yaml
alerting:
  alertmanagers:
    - static_configs:
        - targets:
            - 'alertmanager:9093'

rule_files:
  - /etc/prometheus/rules/*.yml
```

## Relabeling Examples

```yaml
scrape_configs:
  - job_name: 'example'
    static_configs:
      - targets: ['app:8080']
    relabel_configs:
      - source_labels: [__address__]
        regex: '([^:]+):\\d+'
        replacement: '${1}:9117'
        target_label: __param_target
      - source_labels: [__param_target]
        target_label: instance
      - replacement: 'my-app'
        target_label: job
```

---
id: prometheus-intro
title: Prometheus
---

# Prometheus

Prometheus is an open-source systems monitoring and alerting toolkit, designed for reliability and scalability. It records real-time metrics in a time series database built using a HTTP pull model.

## Architecture

This container provides a production-ready Prometheus setup with:

- **Prometheus 3.8.x** latest stable release
- **Environment-driven configuration** for all features
- **Native histogram support** for fine-grained metrics
- **Exemplar storage** for trace integration
- **Memory snapshot on shutdown** for data persistence
- **Config auto-reload** capability

## Prerequisites

Prometheus requires a configuration file mounted at `/opt/containers/config/prometheus.yml`. Without this file, the container will exit with an error.

## Quick Start

```yaml
services:
  prometheus:
    image: ghcr.io/supanadit/containers/prometheus:3.8.1-r2
    container_name: prometheus
    environment:
      PROMETHEUS_PORT: 9090
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

## Configuration

Create a basic `prometheus.yml` configuration file:

```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 15s

scrape_configs:
  - job_name: "prometheus"
    static_configs:
      - targets: ["localhost:9090"]

  - job_name: "my-app"
    static_configs:
      - targets: ["my-app:9100"]
        labels:
          app: "my-application"
```

## Deployment Scenarios

| Scenario | Use Case | Documentation |
|----------|----------|---------------|
| [Basic Setup](/docs/prometheus/prometheus-config) | Single node monitoring | Configuration and env vars |
| [With Thanos](/docs/prometheus/thanos) | Long-term storage | Thanos sidecar integration |
| [Features](/docs/prometheus/prometheus-features) | Advanced features | Native histograms, exemplars, etc. |

## Verifying Prometheus

```bash
# Check Prometheus is healthy
curl http://localhost:9090/-/healthy

# Check Prometheus targets
curl http://localhost:9090/api/v1/targets

# Check Prometheus metrics
curl http://localhost:9090/metrics
```

## Next Steps

- [Configuration](/docs/prometheus/prometheus-config) - Environment variables and configuration

- [Features](/docs/prometheus/prometheus-features) - Enable advanced Prometheus features

- [Thanos Integration](/docs/prometheus/thanos) - Long-term storage with Thanos

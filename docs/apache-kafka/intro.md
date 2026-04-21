---
id: apache-kafka-intro
title: Apache Kafka
---

# Apache Kafka

Apache Kafka is a distributed event streaming platform used for building real-time data pipelines and streaming applications. This container provides a production-ready Kafka setup using **KRaft mode** (Raft-based consensus), eliminating the dependency on Apache ZooKeeper.

## Architecture

This container uses a multi-stage build architecture for optimal caching and security:

```
┌─────────────────────────────────────────────────────────────┐
│                     Build Stage                             │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐    │
│  │   base        │──▶│   setup      │──▶│   runtime     │    │
│  │  (debian)     │  │  (kafka+java)│  │ (+entrypoint)  │    │
│  └───────────────┘  └───────────────┘  └───────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

**Key features:**
- Java 21 runtime
- Kafka 3.9.x with KRaft mode
- Environment-driven configuration
- Health check monitoring
- Graceful shutdown handling

## Quick Start

```yaml
services:
  kafka:
    image: ghcr.io/supanadit/containers/apache-kafka:3.9.2-r0.0.2
    container_name: kafka
    ports:
      - "9092:9092"
    environment:
      KAFKA_NODE_ID: "1"
      KAFKA_PROCESS_ROLES: "broker,controller"
      KAFKA_CONTROLLER_QUORUM_VOTERS: "1@kafka:9093"
      KAFKA_LISTENERS: "PLAINTEXT://:9092,CONTROLLER://:9093"
      KAFKA_ADVERTISED_LISTENERS: "PLAINTEXT://kafka:9092"
      KAFKA_NUM_PARTITIONS: "3"
      KAFKA_LOG_RETENTION_HOURS: "168"
    volumes:
      - kafka_data:/opt/kafka/data
    healthcheck:
      test: ["CMD-SHELL", "pgrep -f kafka.Kafka || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 15
      start_period: 60s

volumes:
  kafka_data:
```

## Deployment Scenarios

| Scenario | Use Case | Node Configuration |
|----------|----------|-------------------|
| [Single Node](/docs/apache-kafka/apache-kafka-kraft#single-node) | Development/testing | 1 combined broker+controller |
| [KRaft Cluster](/docs/apache-kafka/apache-kafka-kraft#kraft-cluster-combined-roles) | Production | 3 combined broker+controller |
| [KRaft Separated](/docs/apache-kafka/apache-kafka-kraft#kraft-cluster-separated-roles) | Large-scale production | 3 controllers + 3 brokers |
| [SASL Authentication](/docs/apache-kafka/apache-kafka-sasl) | Secure client connections | SASL_PLAINTEXT authentication |

## Verifying Cluster Health

```bash
# Check container status
docker compose ps

# View logs
docker compose logs -f kafka-1

# List topics
docker compose exec kafka-1 kafka-topics.sh --bootstrap-server localhost:9092 --list

# Describe a topic
docker compose exec kafka-1 kafka-topics.sh --bootstrap-server localhost:9092 --describe --topic test-topic
```

## Next Steps

- [KRaft Mode](/docs/apache-kafka/apache-kafka-kraft) - Learn about KRaft deployment scenarios
- [Configuration](/docs/apache-kafka/apache-kafka-config) - Environment variables and configuration options
- [SASL Authentication](/docs/apache-kafka/apache-kafka-sasl) - Secure your Kafka cluster with SASL
- [Tips & Tricks](/docs/apache-kafka/apache-kafka-tips) - Best practices and troubleshooting

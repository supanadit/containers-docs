---
id: apache-kafka-kraft
title: KRaft Mode Deployment
---

# KRaft Mode Deployment

Apache Kafka uses **KRaft mode** (Kafka Raft consensus) to manage metadata without requiring Apache ZooKeeper. KRaft provides simplified deployment and improved scalability.

## Single Node

For local development and testing with all-in-one broker and controller:

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

## KRaft Cluster (Combined Roles)

A 3-node cluster where each node runs both broker and controller roles. Suitable for most production workloads:

```yaml
services:
  kafka-1:
    image: ghcr.io/supanadit/containers/apache-kafka:3.9.2-r0.0.2
    container_name: kafka-1
    ports:
      - "9092:9092"
    environment:
      KAFKA_CLUSTER_ID: "4L6I3ZThQamVCg7YvG9fqw"
      KAFKA_NODE_ID: "1"
      KAFKA_PROCESS_ROLES: "broker,controller"
      KAFKA_CONTROLLER_QUORUM_VOTERS: "1@kafka-1:9093,2@kafka-2:9093,3@kafka-3:9093"
      KAFKA_LISTENERS: "PLAINTEXT://:9092,CONTROLLER://:9093"
      KAFKA_ADVERTISED_LISTENERS: "PLAINTEXT://kafka-1:9092"
      KAFKA_NUM_PARTITIONS: "3"
      KAFKA_LOG_RETENTION_HOURS: "168"
      KAFKA_OFFSETS_TOPIC_REPLICATION_FACTOR: "3"
      KAFKA_TRANSACTION_STATE_LOG_REPLICATION_FACTOR: "3"
      KAFKA_TRANSACTION_STATE_LOG_MIN_ISR: "2"
    volumes:
      - kafka_1_data:/opt/kafka/data
    healthcheck:
      test: ["CMD-SHELL", "pgrep -f kafka.Kafka || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 15
      start_period: 60s

  kafka-2:
    image: ghcr.io/supanadit/containers/apache-kafka:3.9.2-r0.0.2
    container_name: kafka-2
    ports:
      - "9093:9092"
    environment:
      KAFKA_CLUSTER_ID: "4L6I3ZThQamVCg7YvG9fqw"
      KAFKA_NODE_ID: "2"
      KAFKA_PROCESS_ROLES: "broker,controller"
      KAFKA_CONTROLLER_QUORUM_VOTERS: "1@kafka-1:9093,2@kafka-2:9093,3@kafka-3:9093"
      KAFKA_LISTENERS: "PLAINTEXT://:9092,CONTROLLER://:9093"
      KAFKA_ADVERTISED_LISTENERS: "PLAINTEXT://kafka-2:9092"
      KAFKA_NUM_PARTITIONS: "3"
      KAFKA_LOG_RETENTION_HOURS: "168"
      KAFKA_OFFSETS_TOPIC_REPLICATION_FACTOR: "3"
      KAFKA_TRANSACTION_STATE_LOG_REPLICATION_FACTOR: "3"
      KAFKA_TRANSACTION_STATE_LOG_MIN_ISR: "2"
    volumes:
      - kafka_2_data:/opt/kafka/data
    healthcheck:
      test: ["CMD-SHELL", "pgrep -f kafka.Kafka || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 15
      start_period: 60s

  kafka-3:
    image: ghcr.io/supanadit/containers/apache-kafka:3.9.2-r0.0.2
    container_name: kafka-3
    ports:
      - "9094:9092"
    environment:
      KAFKA_CLUSTER_ID: "4L6I3ZThQamVCg7YvG9fqw"
      KAFKA_NODE_ID: "3"
      KAFKA_PROCESS_ROLES: "broker,controller"
      KAFKA_CONTROLLER_QUORUM_VOTERS: "1@kafka-1:9093,2@kafka-2:9093,3@kafka-3:9093"
      KAFKA_LISTENERS: "PLAINTEXT://:9092,CONTROLLER://:9093"
      KAFKA_ADVERTISED_LISTENERS: "PLAINTEXT://kafka-3:9092"
      KAFKA_NUM_PARTITIONS: "3"
      KAFKA_LOG_RETENTION_HOURS: "168"
      KAFKA_OFFSETS_TOPIC_REPLICATION_FACTOR: "3"
      KAFKA_TRANSACTION_STATE_LOG_REPLICATION_FACTOR: "3"
      KAFKA_TRANSACTION_STATE_LOG_MIN_ISR: "2"
    volumes:
      - kafka_3_data:/opt/kafka/data
    healthcheck:
      test: ["CMD-SHELL", "pgrep -f kafka.Kafka || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 15
      start_period: 60s

volumes:
  kafka_1_data:
  kafka_2_data:
  kafka_3_data:
```

## KRaft Cluster (Separated Roles)

A 6-node cluster with 3 dedicated controllers and 3 dedicated brokers. Recommended for large-scale production deployments:

```yaml
services:
  controller-1:
    image: ghcr.io/supanadit/containers/apache-kafka:3.9.2-r0.0.2
    container_name: kafka-controller-1
    environment:
      KAFKA_CLUSTER_ID: "4L6I3ZThQamVCg7YvG9fqw"
      KAFKA_NODE_ID: "1"
      KAFKA_PROCESS_ROLES: "controller"
      KAFKA_CONTROLLER_QUORUM_VOTERS: "1@controller-1:9093,2@controller-2:9093,3@controller-3:9093"
      KAFKA_LISTENERS: "CONTROLLER://:9093"
      KAFKA_CONTROLLER_LISTENER_NAMES: "CONTROLLER"
      KAFKA_NUM_NETWORK_THREADS: "1"
      KAFKA_NUM_IO_THREADS: "1"
    volumes:
      - controller_1_data:/opt/kafka/data
    healthcheck:
      test: ["CMD-SHELL", "pgrep -f kafka.Kafka || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 15
      start_period: 60s

  controller-2:
    image: ghcr.io/supanadit/containers/apache-kafka:3.9.2-r0.0.2
    container_name: kafka-controller-2
    environment:
      KAFKA_CLUSTER_ID: "4L6I3ZThQamVCg7YvG9fqw"
      KAFKA_NODE_ID: "2"
      KAFKA_PROCESS_ROLES: "controller"
      KAFKA_CONTROLLER_QUORUM_VOTERS: "1@controller-1:9093,2@controller-2:9093,3@controller-3:9093"
      KAFKA_LISTENERS: "CONTROLLER://:9093"
      KAFKA_CONTROLLER_LISTENER_NAMES: "CONTROLLER"
      KAFKA_NUM_NETWORK_THREADS: "1"
      KAFKA_NUM_IO_THREADS: "1"
    volumes:
      - controller_2_data:/opt/kafka/data
    healthcheck:
      test: ["CMD-SHELL", "pgrep -f kafka.Kafka || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 15
      start_period: 60s

  controller-3:
    image: ghcr.io/supanadit/containers/apache-kafka:3.9.2-r0.0.2
    container_name: kafka-controller-3
    environment:
      KAFKA_CLUSTER_ID: "4L6I3ZThQamVCg7YvG9fqw"
      KAFKA_NODE_ID: "3"
      KAFKA_PROCESS_ROLES: "controller"
      KAFKA_CONTROLLER_QUORUM_VOTERS: "1@controller-1:9093,2@controller-2:9093,3@controller-3:9093"
      KAFKA_LISTENERS: "CONTROLLER://:9093"
      KAFKA_CONTROLLER_LISTENER_NAMES: "CONTROLLER"
      KAFKA_NUM_NETWORK_THREADS: "1"
      KAFKA_NUM_IO_THREADS: "1"
    volumes:
      - controller_3_data:/opt/kafka/data
    healthcheck:
      test: ["CMD-SHELL", "pgrep -f kafka.Kafka || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 15
      start_period: 60s

  broker-1:
    image: ghcr.io/supanadit/containers/apache-kafka:3.9.2-r0.0.2
    container_name: kafka-broker-1
    depends_on:
      - controller-1
      - controller-2
      - controller-3
    ports:
      - "9092:9092"
    environment:
      KAFKA_CLUSTER_ID: "4L6I3ZThQamVCg7YvG9fqw"
      KAFKA_NODE_ID: "4"
      KAFKA_PROCESS_ROLES: "broker"
      KAFKA_CONTROLLER_QUORUM_VOTERS: "1@controller-1:9093,2@controller-2:9093,3@controller-3:9093"
      KAFKA_LISTENERS: "PLAINTEXT://:9092"
      KAFKA_ADVERTISED_LISTENERS: "PLAINTEXT://kafka-broker-1:9092"
      KAFKA_NUM_PARTITIONS: "6"
      KAFKA_LOG_RETENTION_HOURS: "168"
      KAFKA_OFFSETS_TOPIC_REPLICATION_FACTOR: "3"
      KAFKA_TRANSACTION_STATE_LOG_REPLICATION_FACTOR: "3"
      KAFKA_TRANSACTION_STATE_LOG_MIN_ISR: "2"
    volumes:
      - broker_1_data:/opt/kafka/data
    healthcheck:
      test: ["CMD-SHELL", "pgrep -f kafka.Kafka || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 15
      start_period: 60s

  broker-2:
    image: ghcr.io/supanadit/containers/apache-kafka:3.9.2-r0.0.2
    container_name: kafka-broker-2
    depends_on:
      - controller-1
      - controller-2
      - controller-3
    ports:
      - "9093:9092"
    environment:
      KAFKA_CLUSTER_ID: "4L6I3ZThQamVCg7YvG9fqw"
      KAFKA_NODE_ID: "5"
      KAFKA_PROCESS_ROLES: "broker"
      KAFKA_CONTROLLER_QUORUM_VOTERS: "1@controller-1:9093,2@controller-2:9093,3@controller-3:9093"
      KAFKA_LISTENERS: "PLAINTEXT://:9092"
      KAFKA_ADVERTISED_LISTENERS: "PLAINTEXT://kafka-broker-2:9092"
      KAFKA_NUM_PARTITIONS: "6"
      KAFKA_LOG_RETENTION_HOURS: "168"
      KAFKA_OFFSETS_TOPIC_REPLICATION_FACTOR: "3"
      KAFKA_TRANSACTION_STATE_LOG_REPLICATION_FACTOR: "3"
      KAFKA_TRANSACTION_STATE_LOG_MIN_ISR: "2"
    volumes:
      - broker_2_data:/opt/kafka/data
    healthcheck:
      test: ["CMD-SHELL", "pgrep -f kafka.Kafka || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 15
      start_period: 60s

  broker-3:
    image: ghcr.io/supanadit/containers/apache-kafka:3.9.2-r0.0.2
    container_name: kafka-broker-3
    depends_on:
      - controller-1
      - controller-2
      - controller-3
    ports:
      - "9094:9092"
    environment:
      KAFKA_CLUSTER_ID: "4L6I3ZThQamVCg7YvG9fqw"
      KAFKA_NODE_ID: "6"
      KAFKA_PROCESS_ROLES: "broker"
      KAFKA_CONTROLLER_QUORUM_VOTERS: "1@controller-1:9093,2@controller-2:9093,3@controller-3:9093"
      KAFKA_LISTENERS: "PLAINTEXT://:9092"
      KAFKA_ADVERTISED_LISTENERS: "PLAINTEXT://kafka-broker-3:9092"
      KAFKA_NUM_PARTITIONS: "6"
      KAFKA_LOG_RETENTION_HOURS: "168"
      KAFKA_OFFSETS_TOPIC_REPLICATION_FACTOR: "3"
      KAFKA_TRANSACTION_STATE_LOG_REPLICATION_FACTOR: "3"
      KAFKA_TRANSACTION_STATE_LOG_MIN_ISR: "2"
    volumes:
      - broker_3_data:/opt/kafka/data
    healthcheck:
      test: ["CMD-SHELL", "pgrep -f kafka.Kafka || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 15
      start_period: 60s

volumes:
  controller_1_data:
  controller_2_data:
  controller_3_data:
  broker_1_data:
  broker_2_data:
  broker_3_data:
```

## Cluster ID Generation

For multi-node clusters, generate a cluster ID and use it on all nodes:

```bash
# Generate cluster ID
docker compose exec kafka-1 kafka-storage.sh random-uuid
```

Or generate offline:

```bash
docker run --rm ghcr.io/supanadit/containers/apache-kafka:3.9.2-r0.0.2 \
  kafka-storage.sh random-uuid
```

## Scaling KRaft Clusters

When adding nodes to an existing KRaft cluster:

1. Generate a new cluster ID if needed
2. Add the new node to `KAFKA_CONTROLLER_QUORUM_VOTERS` on **all** existing nodes
3. Update `KAFKA_CONTROLLER_QUORUM_VOTERS` on the new node
4. Restart all nodes with updated configuration

```yaml
# Example: Adding kafka-4 to existing 3-node cluster
kafka-4:
  environment:
    KAFKA_NODE_ID: "4"
    KAFKA_CONTROLLER_QUORUM_VOTERS: "1@kafka-1:9093,2@kafka-2:9093,3@kafka-3:9093,4@kafka-4:9093"
```

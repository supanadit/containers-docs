---
id: apache-kafka-tips
title: Tips & Tricks
---

# Tips & Tricks

## Topic Management

```bash
# Create a topic
docker compose exec kafka-1 kafka-topics.sh \
  --bootstrap-server localhost:9092 \
  --create --topic my-topic \
  --partitions 3 --replication-factor 3

# List all topics
docker compose exec kafka-1 kafka-topics.sh \
  --bootstrap-server localhost:9092 \
  --list

# Describe a topic
docker compose exec kafka-1 kafka-topics.sh \
  --bootstrap-server localhost:9092 \
  --describe --topic my-topic

# Delete a topic
docker compose exec kafka-1 kafka-topics.sh \
  --bootstrap-server localhost:9092 \
  --delete --topic my-topic

# Alter topic configuration
docker compose exec kafka-1 kafka-configs.sh \
  --bootstrap-server localhost:9092 \
  --entity-type topics --entity-name my-topic \
  --alter --add-config retention.ms=86400000
```

## Producing and Consuming Messages

```bash
# Produce messages (interactive)
docker compose exec kafka-1 kafka-console-producer.sh \
  --bootstrap-server localhost:9092 \
  --topic my-topic

# Consume messages from beginning
docker compose exec kafka-1 kafka-console-consumer.sh \
  --bootstrap-server localhost:9092 \
  --topic my-topic --from-beginning

# Consume with key/value output
docker compose exec kafka-1 kafka-console-consumer.sh \
  --bootstrap-server localhost:9092 \
  --topic my-topic \
  --property print.key=true \
  --property print.value=true \
  --property key.separator=":"
```

## Verifying Cluster Health

```bash
# Check container status
docker compose ps

# View logs
docker compose logs -f kafka-1

# List cluster metadata
docker compose exec kafka-1 kafka-metadata.sh \
  --snapshot /opt/kafka/data/__cluster_metadata-0/00000000000000000000.log \
  --cluster-id $(cat /opt/kafka/cluster.id)

# Check KRaft cluster status
docker compose exec kafka-1 kafka-storage.sh \
  cluster-id -c /opt/kafka/config/server.properties
```

## Advertised Listeners

The `KAFKA_ADVERTISED_LISTENERS` must match the container hostname for inter-container communication. Use the container name as the hostname:

```yaml
KAFKA_ADVERTISED_LISTENERS: "PLAINTEXT://kafka-1:9092"
```

## Data Directory

The data directory (`/opt/kafka/data`) stores Kafka's commit log files. Ensure:
- Adequate disk space (minimum 10% free)
- Proper permissions (755/700)
- Regular backups

## Maintenance Mode (Sleep Mode)

Set `KAFKA_SLEEP_MODE=true` to start the container in maintenance mode without starting Kafka:

```yaml
environment:
  KAFKA_SLEEP_MODE: "true"
```

This is useful for debugging or running manual Kafka commands.

## Security Context

- Container runs as non-root `kafka` user
- SIGTERM signal for graceful shutdown
- Health checks for process and connectivity monitoring

## KRaft Mode Benefits

KRaft mode provides:
- **Simplified deployment** - No ZooKeeper dependency
- **Improved scalability** - Supports up to 50 brokers
- **Better fault tolerance** - Raft-based consensus protocol

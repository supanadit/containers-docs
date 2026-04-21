---
id: apache-kafka-sasl
title: SASL Authentication
---

# SASL Authentication

Enable SASL_PLAINTEXT authentication for secure client connections.

## Single Node with SASL

```yaml
services:
  kafka:
    image: ghcr.io/supanadit/containers/apache-kafka:3.9.2-r0.0.2
    container_name: kafka-sasl
    ports:
      - "9092:9092"
    environment:
      KAFKA_CLUSTER_ID: "4L6I3ZThQamVCg7YvG9fqw"
      KAFKA_NODE_ID: "1"
      KAFKA_PROCESS_ROLES: "broker,controller"
      KAFKA_CONTROLLER_QUORUM_VOTERS: "1@kafka-sasl:9093"
      KAFKA_LISTENERS: "SASL_PLAINTEXT://:9092,CONTROLLER://:9093"
      KAFKA_ADVERTISED_LISTENERS: "SASL_PLAINTEXT://kafka-sasl:9092"
      KAFKA_INTER_BROKER_LISTENER_NAME: "SASL_PLAINTEXT"
      KAFKA_LISTENER_SECURITY_PROTOCOL_MAP: "CONTROLLER:PLAINTEXT,SASL_PLAINTEXT:SASL_PLAINTEXT"
      KAFKA_SASL_ENABLED_MECHANISMS: "PLAIN"
      KAFKA_SASL_MECHANISM_INTER_BROKER_PROTOCOL: "PLAIN"
      KAFKA_CONFIG_LISTENER_NAME_SASL_PLAINTEXT_PLAIN_SASL_JAAS_CONFIG: 'org.apache.kafka.common.security.plain.PlainLoginModule required username="admin" password="admin-secret" user_admin="admin-secret" user_client="client-secret";'
      KAFKA_NUM_PARTITIONS: "3"
      KAFKA_LOG_RETENTION_HOURS: "168"
      KAFKA_AUTO_CREATE_TOPICS_ENABLE: "false"
      KAFKA_DELETE_TOPIC_ENABLE: "true"
    volumes:
      - kafka_sasl_data:/opt/kafka/data
    healthcheck:
      test: ["CMD-SHELL", "pgrep -f kafka.Kafka || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 15
      start_period: 60s

volumes:
  kafka_sasl_data:
```

## SASL Configuration Reference

| Variable | Description |
|----------|-------------|
| `KAFKA_SASL_ENABLED_MECHANISMS` | SASL mechanisms (`PLAIN`, `SCRAM-SHA-256`, `SCRAM-SHA-512`) |
| `KAFKA_SASL_MECHANISM_INTER_BROKER_PROTOCOL` | SASL mechanism for inter-broker communication |
| `KAFKA_CONFIG_LISTENER_NAME_<LISTENER>_<MECHANISM>_SASL_JAAS_CONFIG` | JAAS configuration for listener |

## Producing and Consuming with SASL

```bash
# Create a JAAS config file for client
cat > /tmp/kafka_client_jaas.conf << EOF
KafkaClient {
  org.apache.kafka.common.security.plain.PlainLoginModule required
  username="admin"
  password="admin-secret";
};
EOF

# Produce messages with SASL
KAFKA_OPTS="-Djava.security.auth.login.config=/tmp/kafka_client_jaas.conf" \
docker compose exec kafka-sasl kafka-console-producer.sh \
  --bootstrap-server localhost:9092 \
  --topic test-topic \
  --producer-property security.protocol=SASL_PLAINTEXT \
  --producer-property sasl.mechanism=PLAIN \
  --producer-property sasl.jaas.config="org.apache.kafka.common.security.plain.PlainLoginModule required username=\"admin\" password=\"admin-secret\";"

# Consume messages with SASL
docker compose exec kafka-sasl kafka-console-consumer.sh \
  --bootstrap-server localhost:9092 \
  --topic test-topic --from-beginning \
  --consumer-property security.protocol=SASL_PLAINTEXT \
  --consumer-property sasl.mechanism=PLAIN \
  --consumer-property sasl.jaas.config="org.apache.kafka.common.security.plain.PlainLoginModule required username=\"admin\" password=\"admin-secret\";"
```

## User Management

The JAAS configuration defines users in the format:

```
user_<username>="<password>"
```

In the example above:
- `user_admin="admin-secret"` - creates user "admin" with password "admin-secret"
- `user_client="client-secret"` - creates user "client" with password "client-secret"

To add more users, update the `KAFKA_CONFIG_LISTENER_NAME_SASL_PLAINTEXT_PLAIN_SASL_JAAS_CONFIG` environment variable:

```yaml
environment:
  KAFKA_CONFIG_LISTENER_NAME_SASL_PLAINTEXT_PLAIN_SASL_JAAS_CONFIG: 'org.apache.kafka.common.security.plain.PlainLoginModule required username="admin" password="admin-secret" user_admin="admin-secret" user_client="client-secret" user_newuser="newpassword";'
```

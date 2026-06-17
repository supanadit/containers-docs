---
id: apache-kafka-config
title: Configuration
---

# Configuration

## Environment Variables

### Core Configuration

| Variable | Description | Default |
|----------|-------------|---------|
| `KAFKA_NODE_ID` | Unique node identifier | `1` |
| `KAFKA_PROCESS_ROLES` | Comma-separated roles: `broker`, `controller` | `broker,controller` |
| `KAFKA_CLUSTER_ID` | Cluster identifier (must be same on all nodes) | Auto-generated |
| `KAFKA_CONTROLLER_QUORUM_VOTERS` | Controller quorum voters (format: `id@host:port[,...]`) | `1@localhost:9093` |
| `KAFKA_CONTROLLER_LISTENER_NAMES` | Controller listener name | `CONTROLLER` |

### Network Configuration

| Variable | Description | Default |
|----------|-------------|---------|
| `KAFKA_LISTENERS` | Listener configuration | `PLAINTEXT://:9092,CONTROLLER://:9093` |
| `KAFKA_ADVERTISED_LISTENERS` | Advertised listeners for clients | `PLAINTEXT://localhost:9092` |
| `KAFKA_INTER_BROKER_LISTENER_NAME` | Inter-broker listener name | `PLAINTEXT` |
| `KAFKA_LISTENER_SECURITY_PROTOCOL_MAP` | Security protocol mapping | `CONTROLLER:PLAINTEXT,PLAINTEXT:PLAINTEXT,...` |

### Performance Configuration

| Variable | Description | Default |
|----------|-------------|---------|
| `KAFKA_NUM_NETWORK_THREADS` | Number of network threads | `3` |
| `KAFKA_NUM_IO_THREADS` | Number of I/O threads | `8` |
| `KAFKA_NUM_PARTITIONS` | Default number of partitions | `1` |
| `KAFKA_NUM_RECOVERY_THREADS_PER_DATA_DIR` | Recovery threads per data dir | `1` |
| `KAFKA_SOCKET_SEND_BUFFER_BYTES` | Socket send buffer size | `102400` |
| `KAFKA_SOCKET_RECEIVE_BUFFER_BYTES` | Socket receive buffer size | `102400` |
| `KAFKA_SOCKET_REQUEST_MAX_BYTES` | Max socket request size | `104857600` |

### Storage Configuration

| Variable | Description | Default |
|----------|-------------|---------|
| `KAFKA_LOG_DIRS` | Log directories | `/opt/containers/data` |
| `KAFKA_LOG_RETENTION_HOURS` | Log retention period (hours) | `168` |
| `KAFKA_LOG_SEGMENT_BYTES` | Log segment size | `1073741824` |
| `KAFKA_LOG_RETENTION_CHECK_INTERVAL_MS` | Retention check interval | `300000` |

### Replication Configuration

| Variable | Description | Default |
|----------|-------------|---------|
| `KAFKA_OFFSETS_TOPIC_REPLICATION_FACTOR` | Offsets topic replication factor | `1` |
| `KAFKA_TRANSACTION_STATE_LOG_REPLICATION_FACTOR` | Transaction log replication factor | `1` |
| `KAFKA_TRANSACTION_STATE_LOG_MIN_ISR` | Minimum in-sync replicas | `1` |

### Topic Configuration

| Variable | Description | Default |
|----------|-------------|---------|
| `KAFKA_AUTO_CREATE_TOPICS_ENABLE` | Auto-create topics | `true` |
| `KAFKA_DELETE_TOPIC_ENABLE` | Allow topic deletion | `true` |
| `KAFKA_GROUP_INITIAL_REBALANCE_DELAY_MS` | Group rebalance delay | `0` |

## Custom Properties

Any Kafka property can be set using the `KAFKA_CONFIG_` prefix:

```yaml
environment:
  KAFKA_CONFIG_LOG_CLEANER_ENABLE: "true"
  KAFKA_CONFIG_LOG_RETENTION_BYTES: "1073741824"
  KAFKA_CONFIG_MAX_REQUEST_SIZE: "10485760"
  KAFKA_CONFIG_MESSAGE_MAX_BYTES: "1048588"
  KAFKA_CONFIG_REPLICA_FETCH_MAX_BYTES: "1048576"
  KAFKA_CONFIG_MAX_CONNECTIONS_PER_IP: "2147483647"
```

This translates to:
- `log.cleaner.enable=true`
- `log.retention.bytes=1073741824`
- `max.request.size=10485760`
- `message.max.bytes=1048588`
- `replica.fetch.max.bytes=1048576`
- `max.connections.per.ip=2147483647`

## Listener Configuration Example

For multiple listener configurations:

```yaml
environment:
  KAFKA_LISTENERS: "PLAINTEXT://:9092,CONTROLLER://:9093"
  KAFKA_ADVERTISED_LISTENERS: "PLAINTEXT://kafka:9092"
  KAFKA_LISTENER_SECURITY_PROTOCOL_MAP: "CONTROLLER:PLAINTEXT,PLAINTEXT:PLAINTEXT"
  KAFKA_INTER_BROKER_LISTENER_NAME: "PLAINTEXT"
```

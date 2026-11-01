# Apache Kafka

Apache Kafka 3.9 in KRaft mode — no ZooKeeper. The image generates
`server.properties` from environment variables, formats the KRaft storage
idempotently on first boot, and supervises the broker with the same health
endpoint as every other container in this collection.

## Quickstart

```bash
docker run -d \
  --name kafka \
  -v kafka-data:/opt/containers/data \
  -p 9092:9092 \
  ghcr.io/supanadit/containers/apache-kafka
```

The defaults give you a single combined broker + controller listening on
`PLAINTEXT://:9092` and advertising `PLAINTEXT://localhost:9092` — good for
local development. If clients connect from other machines, set the advertised
listener to a routable hostname:

```bash
docker run -d \
  --name kafka \
  -e KAFKA_ADVERTISED_LISTENERS=PLAINTEXT://kafka.example.com:9092 \
  -v kafka-data:/opt/containers/data \
  -p 9092:9092 \
  ghcr.io/supanadit/containers/apache-kafka
```

## Configuration

| Variable | Default | Description |
| --- | --- | --- |
| `KAFKA_NODE_ID` | `1` | KRaft node id; must be unique per node. |
| `KAFKA_PROCESS_ROLES` | `broker,controller` | Combined role by default; use `broker` or `controller` alone for split roles. |
| `KAFKA_CONTROLLER_QUORUM_VOTERS` | `1@localhost:9093` | KRaft quorum definition, `id@host:port` comma-separated. |
| `KAFKA_LISTENERS` | `PLAINTEXT://:9092,CONTROLLER://:9093` | Listener definitions. |
| `KAFKA_ADVERTISED_LISTENERS` | `PLAINTEXT://localhost:9092` | Address handed to clients; set a routable host in multi-host setups. |
| `KAFKA_LISTENER_SECURITY_PROTOCOL_MAP` | `CONTROLLER:PLAINTEXT,PLAINTEXT:PLAINTEXT,...` | Maps each listener name to its protocol (`SSL`, `SASL_SSL`, ...). |
| `KAFKA_INTER_BROKER_LISTENER_NAME` | `PLAINTEXT` | Listener brokers use to talk to each other. |
| `KAFKA_CLUSTER_ID` | *(generated)* | Pin the KRaft cluster id; otherwise generated and persisted. |
| `KAFKA_NUM_PARTITIONS` | `1` | Default partitions for auto-created topics. |
| `KAFKA_OFFSETS_TOPIC_REPLICATION_FACTOR` | `1` | Raise to 3 in a real cluster. |
| `KAFKA_TRANSACTION_STATE_LOG_REPLICATION_FACTOR` | `1` | Raise to 3 in a real cluster. |
| `KAFKA_TRANSACTION_STATE_LOG_MIN_ISR` | `1` | Minimum in-sync replicas for the transaction log. |
| `KAFKA_LOG_RETENTION_HOURS` | `168` | How long log segments are kept. |
| `KAFKA_LOG_SEGMENT_BYTES` | `1073741824` | Segment rollover size. |
| `KAFKA_AUTO_CREATE_TOPICS_ENABLE` | `true` | Allow clients to implicitly create topics. |
| `KAFKA_DELETE_TOPIC_ENABLE` | `true` | Allow topic deletion. |
| `KAFKA_HEAP_OPTS` | *(empty)* | JVM heap, e.g. `-Xmx1g -Xms1g`. |
| `KAFKA_LOG4J_ROOT_LOGLEVEL` | `INFO` | Broker log level (`LOG_LEVEL` also works). |

### TLS

Set `KAFKA_SSL_ENABLE=true` and either provide PKCS12/JKS stores or inline PEM.
TLS applies to every `SSL`/`SASL_SSL` listener.

| Variable | Default | Description |
| --- | --- | --- |
| `KAFKA_SSL_ENABLE` | `false` | Enables the TLS settings below. |
| `KAFKA_SSL_KEYSTORE_TYPE` | `PKCS12` | `PKCS12`, `JKS` or `PEM`. |
| `KAFKA_SSL_KEYSTORE_LOCATION` | *(empty)* | Path to the keystore (mount it, e.g. under `/opt/containers/config`). |
| `KAFKA_SSL_KEYSTORE_PASSWORD` | *(empty)* | Keystore password. |
| `KAFKA_SSL_KEY_PASSWORD` | *(empty)* | Private key password. |
| `KAFKA_SSL_TRUSTSTORE_TYPE` | `PKCS12` | Truststore type. |
| `KAFKA_SSL_TRUSTSTORE_LOCATION` | *(empty)* | Path to the truststore. |
| `KAFKA_SSL_TRUSTSTORE_PASSWORD` | *(empty)* | Truststore password. |
| `KAFKA_SSL_CLIENT_AUTH` | `none` | `none`, `required` or `requested` (mTLS). |
| `KAFKA_SSL_PROTOCOL` | `TLSv1.3` | TLS protocol. |
| `KAFKA_SSL_ENABLED_PROTOCOLS` | `TLSv1.3,TLSv1.2` | Allowed protocol list. |

For PEM mode (`KAFKA_SSL_KEYSTORE_TYPE=PEM`) use `KAFKA_SSL_KEYSTORE_KEY`,
`KAFKA_SSL_KEYSTORE_CERTIFICATE_CHAIN` and `KAFKA_SSL_TRUSTSTORE_CERTIFICATES`
instead of the store files.

### SASL

| Variable | Default | Description |
| --- | --- | --- |
| `KAFKA_SASL_ENABLED_MECHANISMS` | `PLAIN` | Comma-separated mechanisms (`PLAIN`, `SCRAM-SHA-256`, `SCRAM-SHA-512`). |
| `KAFKA_SASL_MECHANISM_INTER_BROKER_PROTOCOL` | `PLAIN` | Mechanism for broker-to-broker traffic. |
| `KAFKA_SASL_JAAS_CONFIG` | *(empty)* | Global JAAS config applied to every SASL listener; per-listener overrides use `KAFKA_SASL_JAAS_CONFIG_<LISTENER_NAME>`. |
| `KAFKA_SASL_SCRAM_USERS` | *(empty)* | `user:pass[,user:pass...]`; SCRAM credentials are created via `kafka-configs.sh` once the broker is ready. |

> [!TIP]
> A typical SASL_SSL listener is three variables: add
> `SASL_SSL://:9094` to `KAFKA_LISTENERS`, map it with
> `KAFKA_LISTENER_SECURITY_PROTOCOL_MAP`, and advertise it. The security map
> already ships the standard entries.

## Health

The broker is combined-mode KRaft by default, so the health endpoint composites
an Apache ZooKeeper-free broker check: the ezx `/health` API listens on port
8080 and reports the broker listener TCP probe plus KRaft readiness.

```bash
curl -s http://localhost:8080/health
```

> [!NOTE]
> If `KAFKA_CONTROLLER_QUORUM_VOTERS` points at other nodes, they must all be
> reachable before the quorum elects a leader — health stays degraded until
> then. See [Health & readiness](/docs/health) for the endpoint contract.

## KRaft bootstrap

On first boot the entrypoint resolves a cluster id in this order:
`KAFKA_CLUSTER_ID`, then `meta.properties` in the data directory, then the
persisted `cluster.id` file, and finally a freshly generated
`kafka-storage.sh random-uuid`. Storage is then formatted with
`--ignore-formatted`, so restarts are a no-op.

Two safety rails matter when mounting volumes:

- An explicit `KAFKA_CLUSTER_ID` that disagrees with `meta.properties` in the
  data directory is fatal — unset it or use a fresh volume.
- A non-empty data directory that is not KRaft-formatted refuses to format, so
  a mis-mounted volume never gets silently wiped.

## Clustering

A three-node combined cluster only needs per-node identity and the shared
quorum definition:

```yaml
services:
  kafka-1: &kafka
    image: ghcr.io/supanadit/containers/apache-kafka
    environment:
      KAFKA_NODE_ID: "1"
      KAFKA_CONTROLLER_QUORUM_VOTERS: "1@kafka-1:9093,2@kafka-2:9093,3@kafka-3:9093"
      KAFKA_ADVERTISED_LISTENERS: "PLAINTEXT://kafka-1:9092"
      KAFKA_LISTENERS: "PLAINTEXT://:9092,CONTROLLER://:9093"
    volumes: [kafka-1-data:/opt/containers/data]
  kafka-2:
    <<: *kafka
    environment:
      KAFKA_NODE_ID: "2"
      KAFKA_CONTROLLER_QUORUM_VOTERS: "1@kafka-1:9093,2@kafka-2:9093,3@kafka-3:9093"
      KAFKA_ADVERTISED_LISTENERS: "PLAINTEXT://kafka-2:9092"
    volumes: [kafka-2-data:/opt/containers/data]
  kafka-3:
    <<: *kafka
    environment:
      KAFKA_NODE_ID: "3"
      KAFKA_CONTROLLER_QUORUM_VOTERS: "1@kafka-1:9093,2@kafka-2:9093,3@kafka-3:9093"
      KAFKA_ADVERTISED_LISTENERS: "PLAINTEXT://kafka-3:9092"
    volumes: [kafka-3-data:/opt/containers/data]
```

In a multi-node cluster also raise
`KAFKA_OFFSETS_TOPIC_REPLICATION_FACTOR`, the transaction-state replication
factor and `KAFKA_TRANSACTION_STATE_LOG_MIN_ISR` (typically to 3 and 2).

## Metadata backup

KRaft removes ZooKeeper but the metadata log still deserves backups. Enable the
scheduled snapshot with `KAFKA_METADATA_BACKUP_ENABLE=true`; it runs via the
ezx scheduler into `/opt/containers/backup` with `KAFKA_METADATA_BACKUP_SCHEDULE`
(cron, default `0 3 * * *`), `KAFKA_METADATA_BACKUP_TIMEZONE` (default `UTC`)
and `KAFKA_METADATA_BACKUP_RETENTION_DAYS` (default `7`).

## Next steps

- [Configuration](/docs/configuration) — how env vars become config files.
- [Volumes](/docs/volumes) — the `/opt/containers/*` layout.
- [Apache ZooKeeper](/docs/apache-zookeeper) — only needed for legacy,
  non-KRaft clients.

# etcd

etcd 3.6, built from source and supervised by
[ezx](https://github.com/supanadit/ezx). Every etcd flag this image exposes is
set through environment variables — the entrypoint maps them to command-line
flags, keeps `ETCD_*` variables out of the child environment, and serves the
shared composite health endpoint. It runs as a dedicated non-root `etcd` user.

## Quickstart

```bash
docker run -d \
  --name etcd \
  -v etcd-data:/opt/containers/data \
  -p 2379:2379 \
  ghcr.io/supanadit/containers/etcd
```

Write and read a key:

```bash
docker exec -it etcd etcdctl put /greeting hello
docker exec -it etcd etcdctl get /greeting
curl -s http://localhost:8080/health
```

## Configuration

| Variable | Default | Description |
| --- | --- | --- |
| `ETCD_NAME` | `etcd-node` | Node name; must be unique in a cluster. |
| `ETCD_LISTEN_CLIENT_URLS` | `http://0.0.0.0:2379` | URLs clients connect to. |
| `ETCD_LISTEN_PEER_URLS` | `http://0.0.0.0:2380` | URLs peers connect to. |
| `ETCD_ADVERTISE_CLIENT_URLS` | `http://<container-ip>:2379` | Empty means the container's own IP, resolved at startup. |
| `ETCD_INITIAL_ADVERTISE_PEER_URLS` | `http://<container-ip>:2380` | Peer URL advertised to the cluster. |
| `ETCD_INITIAL_CLUSTER` | *(this node)* | `name=peerURL` list; set for multi-node clusters. |
| `ETCD_INITIAL_CLUSTER_STATE` | `new` | `new` for a fresh cluster, `existing` when joining one. |
| `ETCD_INITIAL_CLUSTER_TOKEN` | `etcd-cluster` | Unique cluster token; keeps two clusters from cross-talking. |
| `ETCD_HEARTBEAT_INTERVAL` | `1000` | Leader heartbeat in ms. |
| `ETCD_ELECTION_TIMEOUT` | `5000` | Follower election timeout in ms. |
| `ETCD_SNAPSHOT_COUNT` | `5000` | Raft entries before a backend snapshot. |
| `ETCD_AUTO_COMPACTION_RETENTION` | `1` | Auto-compaction retention (passed straight to etcd). |
| `ETCD_MAX_REQUEST_BYTES` | `10485760` | Max request size (10 MiB). |
| `ETCD_DATA_DIR` | `/opt/containers/data` | Raft log and MVCC store. |
| `SLEEP_MODE` | `false` | Start the supervisor but not etcd (debugging). |

### TLS

TLS is opt-in per listener; enable `ETCD_CLIENT_CERT_AUTH` (mTLS) to require
client certificates on the client port.

| Variable | Default | Description |
| --- | --- | --- |
| `ETCD_CERT_FILE` | *(empty)* | Server certificate for the client listener. |
| `ETCD_KEY_FILE` | *(empty)* | Server key for the client listener. |
| `ETCD_CLIENT_CERT_AUTH` | `false` | Require client certificates. |
| `ETCD_TRUSTED_CA_FILE` | *(empty)* | CA verifying client certificates. |
| `ETCD_PEER_CERT_FILE` | *(empty)* | Certificate for the peer listener. |
| `ETCD_PEER_KEY_FILE` | *(empty)* | Key for the peer listener. |
| `ETCD_PEER_CLIENT_CERT_AUTH` | `false` | Require peer certificates (peer mTLS). |
| `ETCD_PEER_TRUSTED_CA_FILE` | *(empty)* | CA verifying peer certificates. |

Mount certificates somewhere readable (e.g. `/opt/containers/config/certs`)
and reference the paths.

## Health

The health check verifies etcd answers on its client port, and the ezx API on
port 8080 serves the composite `/health` endpoint:

```bash
curl -s http://localhost:8080/health
```

> [!TIP]
> Inside the cluster you can also use the native endpoint:
> `curl http://localhost:2379/health`. Orchestrators should probe the
> composite endpoint — see [Health & readiness](/docs/health).

## Clustering

A static three-node cluster sets the initial cluster explicitly. Advertising
URLs matter: each node must be reachable from its peers, so fix them instead
of relying on the container-IP default.

```yaml
services:
  etcd-1:
    image: ghcr.io/supanadit/containers/etcd
    environment:
      ETCD_NAME: infra1
      ETCD_INITIAL_ADVERTISE_PEER_URLS: "http://etcd-1:2380"
      ETCD_ADVERTISE_CLIENT_URLS: "http://etcd-1:2379"
      ETCD_INITIAL_CLUSTER: "infra1=http://etcd-1:2380,infra2=http://etcd-2:2380,infra3=http://etcd-3:2380"
      ETCD_INITIAL_CLUSTER_STATE: "new"
    volumes: [etcd-1-data:/opt/containers/data]
  etcd-2:
    image: ghcr.io/supanadit/containers/etcd
    environment:
      ETCD_NAME: infra2
      ETCD_INITIAL_ADVERTISE_PEER_URLS: "http://etcd-2:2380"
      ETCD_ADVERTISE_CLIENT_URLS: "http://etcd-2:2379"
      ETCD_INITIAL_CLUSTER: "infra1=http://etcd-1:2380,infra2=http://etcd-2:2380,infra3=http://etcd-3:2380"
      ETCD_INITIAL_CLUSTER_STATE: "new"
    volumes: [etcd-2-data:/opt/containers/data]
```

> [!NOTE]
> The `ETCD_*` variables are stripped from the etcd process environment at
> spawn time, so the explicit flags are the single source of truth and a
> stray `ETCD_*` variable cannot conflict with them.

## Next steps

- [Configuration](/docs/configuration) — how env vars become flags.
- [Volumes](/docs/volumes) — the `/opt/containers/*` layout.
- [Apache Kafka](/docs/apache-kafka) — KRaft replaces ZooKeeper for Kafka
  metadata; etcd is not part of the Kafka stack.

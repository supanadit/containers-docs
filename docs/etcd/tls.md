---
id: etcd-tls
title: TLS Configuration
---

# TLS Configuration

Secure ETCD cluster communication with TLS certificates.

## Generate TLS Certificates

Use the provided script to generate TLS certificates:

```bash
# Generate TLS certificates
./generate-tls.sh

# This creates:
# - tls/ca.pem          - CA certificate
# - tls/server.pem      - Server certificate
# - tls/server-key.pem  - Server key
# - tls/client.pem      - Client certificate
# - tls/client-key.pem  - Client key
# - tls/peer.pem        - Peer certificate
# - tls/peer-key.pem    - Peer key
```

## TLS 3-Node Cluster

```yaml
services:
  etcd-1:
    image: ghcr.io/supanadit/containers/etcd:3.6.7-r4
    container_name: etcd-1
    environment:
      ETCDCTL_API: "3"
      ETCD_CERT_FILE: /tls/server.pem
      ETCD_KEY_FILE: /tls/server-key.pem
      ETCD_CLIENT_CERT_AUTH: "true"
      ETCD_TRUSTED_CA_FILE: /tls/ca.pem
      ETCD_PEER_CERT_FILE: /tls/peer.pem
      ETCD_PEER_KEY_FILE: /tls/peer-key.pem
      ETCD_PEER_CLIENT_CERT_AUTH: "true"
      ETCD_PEER_TRUSTED_CA_FILE: /tls/ca.pem
    command:
      - /usr/local/bin/etcd
      - --name=etcd-1
      - --data-dir=/var/lib/etcd
      - --listen-peer-urls=https://0.0.0.0:2380
      - --listen-client-urls=https://0.0.0.0:2379
      - --advertise-client-urls=https://etcd-1:2379
      - --initial-advertise-peer-urls=https://etcd-1:2380
      - --initial-cluster=etcd-1=https://etcd-1:2380,etcd-2=https://etcd-2:2380,etcd-3=https://etcd-3:2380
      - --initial-cluster-state=new
      - --initial-cluster-token=etcd-production-cluster
      - --client-cert-auth
      - --peer-client-cert-auth
      - --heartbeat-interval=1000
      - --election-timeout=5000
      - --snapshot-count=5000
      - --auto-compaction-retention=1
      - --max-request-bytes=10485760
    volumes:
      - etcd_1_data:/var/lib/etcd
      - ./tls/ca.pem:/tls/ca.pem:ro
      - ./tls/server.pem:/tls/server.pem:ro
      - ./tls/server-key.pem:/tls/server-key.pem:ro
      - ./tls/peer.pem:/tls/peer.pem:ro
      - ./tls/peer-key.pem:/tls/peer-key.pem:ro
    healthcheck:
      test: ["CMD", "etcdctl", "--endpoints=https://127.0.0.1:2379", "--cert=/tls/client.pem", "--key=/tls/client-key.pem", "--cacert=/tls/ca.pem", "endpoint", "health"]
      interval: 10s
      timeout: 5s
      retries: 5
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '0.5'
    restart: unless-stopped

  etcd-2:
    image: ghcr.io/supanadit/containers/etcd:3.6.7-r4
    container_name: etcd-2
    environment:
      ETCDCTL_API: "3"
      ETCD_CERT_FILE: /tls/server.pem
      ETCD_KEY_FILE: /tls/server-key.pem
      ETCD_CLIENT_CERT_AUTH: "true"
      ETCD_TRUSTED_CA_FILE: /tls/ca.pem
      ETCD_PEER_CERT_FILE: /tls/peer.pem
      ETCD_PEER_KEY_FILE: /tls/peer-key.pem
      ETCD_PEER_CLIENT_CERT_AUTH: "true"
      ETCD_PEER_TRUSTED_CA_FILE: /tls/ca.pem
    command:
      - /usr/local/bin/etcd
      - --name=etcd-2
      - --data-dir=/var/lib/etcd
      - --listen-peer-urls=https://0.0.0.0:2380
      - --listen-client-urls=https://0.0.0.0:2379
      - --advertise-client-urls=https://etcd-2:2379
      - --initial-advertise-peer-urls=https://etcd-2:2380
      - --initial-cluster=etcd-1=https://etcd-1:2380,etcd-2=https://etcd-2:2380,etcd-3=https://etcd-3:2380
      - --initial-cluster-state=new
      - --initial-cluster-token=etcd-production-cluster
      - --client-cert-auth
      - --peer-client-cert-auth
      - --heartbeat-interval=1000
      - --election-timeout=5000
      - --snapshot-count=5000
      - --auto-compaction-retention=1
      - --max-request-bytes=10485760
    volumes:
      - etcd_2_data:/var/lib/etcd
      - ./tls/ca.pem:/tls/ca.pem:ro
      - ./tls/server.pem:/tls/server.pem:ro
      - ./tls/server-key.pem:/tls/server-key.pem:ro
      - ./tls/peer.pem:/tls/peer.pem:ro
      - ./tls/peer-key.pem:/tls/peer-key.pem:ro
    healthcheck:
      test: ["CMD", "etcdctl", "--endpoints=https://127.0.0.1:2379", "--cert=/tls/client.pem", "--key=/tls/client-key.pem", "--cacert=/tls/ca.pem", "endpoint", "health"]
      interval: 10s
      timeout: 5s
      retries: 5
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '0.5'
    restart: unless-stopped
    depends_on:
      etcd-1:
        condition: service_healthy

  etcd-3:
    image: ghcr.io/supanadit/containers/etcd:3.6.7-r4
    container_name: etcd-3
    environment:
      ETCDCTL_API: "3"
      ETCD_CERT_FILE: /tls/server.pem
      ETCD_KEY_FILE: /tls/server-key.pem
      ETCD_CLIENT_CERT_AUTH: "true"
      ETCD_TRUSTED_CA_FILE: /tls/ca.pem
      ETCD_PEER_CERT_FILE: /tls/peer.pem
      ETCD_PEER_KEY_FILE: /tls/peer-key.pem
      ETCD_PEER_CLIENT_CERT_AUTH: "true"
      ETCD_PEER_TRUSTED_CA_FILE: /tls/ca.pem
    command:
      - /usr/local/bin/etcd
      - --name=etcd-3
      - --data-dir=/var/lib/etcd
      - --listen-peer-urls=https://0.0.0.0:2380
      - --listen-client-urls=https://0.0.0.0:2379
      - --advertise-client-urls=https://etcd-3:2379
      - --initial-advertise-peer-urls=https://etcd-3:2380
      - --initial-cluster=etcd-1=https://etcd-1:2380,etcd-2=https://etcd-2:2380,etcd-3=https://etcd-3:2380
      - --initial-cluster-state=new
      - --initial-cluster-token=etcd-production-cluster
      - --client-cert-auth
      - --peer-client-cert-auth
      - --heartbeat-interval=1000
      - --election-timeout=5000
      - --snapshot-count=5000
      - --auto-compaction-retention=1
      - --max-request-bytes=10485760
    volumes:
      - etcd_3_data:/var/lib/etcd
      - ./tls/ca.pem:/tls/ca.pem:ro
      - ./tls/server.pem:/tls/server.pem:ro
      - ./tls/server-key.pem:/tls/server-key.pem:ro
      - ./tls/peer.pem:/tls/peer.pem:ro
      - ./tls/peer-key.pem:/tls/peer-key.pem:ro
    healthcheck:
      test: ["CMD", "etcdctl", "--endpoints=https://127.0.0.1:2379", "--cert=/tls/client.pem", "--key=/tls/client-key.pem", "--cacert=/tls/ca.pem", "endpoint", "health"]
      interval: 10s
      timeout: 5s
      retries: 5
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '0.5'
    restart: unless-stopped
    depends_on:
      etcd-2:
        condition: service_healthy

volumes:
  etcd_1_data:
  etcd_2_data:
  etcd_3_data:

networks:
  default:
    name: etcd-tls-network
    driver: bridge
```

## TLS Environment Variables

| Variable | Description |
|----------|-------------|
| `ETCD_CERT_FILE` | TLS certificate file for client connections |
| `ETCD_KEY_FILE` | TLS key file for client connections |
| `ETCD_CLIENT_CERT_AUTH` | Enable client certificate authentication |
| `ETCD_TRUSTED_CA_FILE` | Trusted CA certificate file |
| `ETCD_PEER_CERT_FILE` | TLS certificate file for peer connections |
| `ETCD_PEER_KEY_FILE` | TLS key file for peer connections |
| `ETCD_PEER_CLIENT_CERT_AUTH` | Enable peer certificate authentication |
| `ETCD_PEER_TRUSTED_CA_FILE` | Trusted CA file for peers |

## Testing TLS Connections

```bash
# Check cluster health with TLS
docker compose exec etcd-1 etcdctl endpoint health \
  --cert=/tls/client.pem \
  --key=/tls/client-key.pem \
  --cacert=/tls/ca.pem

# Get a key with TLS
docker compose exec etcd-1 etcdctl get mykey \
  --cert=/tls/client.pem \
  --key=/tls/client-key.pem \
  --cacert=/tls/ca.pem
```

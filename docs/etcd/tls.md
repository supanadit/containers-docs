---
id: etcd-tls
title: TLS Configuration
---

# TLS Configuration

Secure ETCD cluster communication with TLS certificates. The container supports TLS via environment variables - you need to provide your own certificates.

## TLS 3-Node Cluster

```yaml
services:
  etcd-1:
    image: ghcr.io/supanadit/containers/etcd:3.6.7-r4
    container_name: etcd-1
    environment:
      ETCD_CERT_FILE: /tls/server.pem
      ETCD_KEY_FILE: /tls/server-key.pem
      ETCD_CLIENT_CERT_AUTH: "true"
      ETCD_TRUSTED_CA_FILE: /tls/ca.pem
      ETCD_PEER_CERT_FILE: /tls/peer.pem
      ETCD_PEER_KEY_FILE: /tls/peer-key.pem
      ETCD_PEER_CLIENT_CERT_AUTH: "true"
      ETCD_PEER_TRUSTED_CA_FILE: /tls/ca.pem
    volumes:
      - etcd_1_data:/opt/containers/data
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

  etcd-2:
    image: ghcr.io/supanadit/containers/etcd:3.6.7-r4
    container_name: etcd-2
    environment:
      ETCD_CERT_FILE: /tls/server.pem
      ETCD_KEY_FILE: /tls/server-key.pem
      ETCD_CLIENT_CERT_AUTH: "true"
      ETCD_TRUSTED_CA_FILE: /tls/ca.pem
      ETCD_PEER_CERT_FILE: /tls/peer.pem
      ETCD_PEER_KEY_FILE: /tls/peer-key.pem
      ETCD_PEER_CLIENT_CERT_AUTH: "true"
      ETCD_PEER_TRUSTED_CA_FILE: /tls/ca.pem
    volumes:
      - etcd_2_data:/opt/containers/data
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

  etcd-3:
    image: ghcr.io/supanadit/containers/etcd:3.6.7-r4
    container_name: etcd-3
    environment:
      ETCD_CERT_FILE: /tls/server.pem
      ETCD_KEY_FILE: /tls/server-key.pem
      ETCD_CLIENT_CERT_AUTH: "true"
      ETCD_TRUSTED_CA_FILE: /tls/ca.pem
      ETCD_PEER_CERT_FILE: /tls/peer.pem
      ETCD_PEER_KEY_FILE: /tls/peer-key.pem
      ETCD_PEER_CLIENT_CERT_AUTH: "true"
      ETCD_PEER_TRUSTED_CA_FILE: /tls/ca.pem
    volumes:
      - etcd_3_data:/opt/containers/data
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

volumes:
  etcd_1_data:
  etcd_2_data:
  etcd_3_data:
```

## TLS Environment Variables

| Variable | Description |
|----------|-------------|
| `ETCD_CERT_FILE` | TLS certificate file for client connections |
| `ETCD_KEY_FILE` | TLS key file for client connections |
| `ETCD_CLIENT_CERT_AUTH` | Enable client certificate authentication (`true`/`false`) |
| `ETCD_TRUSTED_CA_FILE` | Trusted CA certificate file |
| `ETCD_PEER_CERT_FILE` | TLS certificate file for peer connections |
| `ETCD_PEER_KEY_FILE` | TLS key file for peer connections |
| `ETCD_PEER_CLIENT_CERT_AUTH` | Enable peer certificate authentication (`true`/`false`) |
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

# Put a key with TLS
docker compose exec etcd-1 etcdctl put mykey myvalue \
  --cert=/tls/client.pem \
  --key=/tls/client-key.pem \
  --cacert=/tls/ca.pem
```

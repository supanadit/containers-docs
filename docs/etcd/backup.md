---
id: etcd-backup
title: Backup & Restore
---

# Backup & Restore

ETCD supports snapshots for backup and disaster recovery using `etcdctl`.

## Backup

### Create a Backup

```bash
# Set up environment variables
export ETCD_ENDPOINTS="https://etcd-1:2379,https://etcd-2:2379,https://etcd-3:2379"
export CERT_FILE="./tls/client.pem"
export KEY_FILE="./tls/client-key.pem"
export CA_FILE="./tls/ca.pem"
export BACKUP_DIR="./backups"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Take a snapshot
docker compose exec etcd-1 etcdctl snapshot save "$BACKUP_DIR/etcd-snapshot-$(date +%Y%m%d_%H%M%S).db" \
  --endpoints="$ETCD_ENDPOINTS" \
  --cert="$CERT_FILE" \
  --key="$KEY_FILE" \
  --cacert="$CA_FILE"
```

### Automated Backup Script

Use the provided `backup.sh` script:

```bash
# Take a backup
./backup.sh backup

# List existing backups
./backup.sh list

# Restore from a backup
./backup.sh restore ./backups/etcd-snapshot-20240101_120000.db
```

The script supports:
- **backup** - Take a new snapshot with compression
- **restore** - Restore from a snapshot file
- **list** - List available snapshots
- Automatic verification after backup
- Retention policy for old snapshots

### Environment Variables for Backup

| Variable | Description | Default |
|----------|-------------|---------|
| `BACKUP_DIR` | Directory to store backups | `./backups` |
| `ETCD_ENDPOINTS` | ETCD endpoints | `https://etcd-1:2379,...` |
| `CERT_FILE` | TLS certificate file | `./tls/client.pem` |
| `KEY_FILE` | TLS key file | `./tls/client-key.pem` |
| `CA_FILE` | TLS CA file | `./tls/ca.pem` |
| `RETENTION_DAYS` | Days to keep backups | `7` |
| `SNAPSHOT_NAME` | Prefix for snapshot files | `etcd-snapshot` |

## Restore

### Restore from Snapshot

```bash
# Stop the cluster
docker compose down

# Restore on each node
docker compose run --rm etcd-1 etcdctl snapshot restore ./backups/etcd-snapshot-20240101_120000.db \
  --data-dir=/var/lib/etcd \
  --endpoints=https://etcd-1:2379 \
  --cert=/tls/client.pem \
  --key=/tls/client-key.pem \
  --cacert=/tls/ca.pem

# Restart the cluster
docker compose up -d
```

### Restore to a New Data Directory

```bash
# Restore to a new directory (useful for testing)
docker compose run --rm etcd-1 etcdctl snapshot restore ./backups/etcd-snapshot-20240101_120000.db \
  --data-dir=/var/lib/etcd-restored \
  --name=etcd-1 \
  --initial-cluster=etcd-1=http://etcd-1:2380 \
  --initial-cluster-token=etcd-restored-cluster \
  --initial-advertise-peer-urls=http://etcd-1:2380
```

## Verify Backup Integrity

```bash
# Verify a backup without modifying it
gunzip -c ./backups/etcd-snapshot-20240101_120000.db.gz | \
  docker compose run --rm etcd-1 etcdctl snapshot restore - \
  --data-dir=/tmp/etcd-verify \
  --skip-hash-check

# Cleanup verification directory
rm -rf /tmp/etcd-verify
```

## Important Notes

### Backup Frequency

- **Development**: Daily backups may be sufficient
- **Production**: Consider hourly or continuous backup with etcd's watch feature
- **Before Upgrades**: Always backup before upgrading ETCD

### Retention Policy

Implement a retention policy to manage backup storage:

```bash
# Find and delete backups older than 7 days
find ./backups -name "etcd-snapshot-*.db.gz" -mtime +7 -delete
```

### Network Connectivity

Ensure backup scripts can reach all ETCD endpoints. If one node is down, backups can still work if at least one endpoint is available.

---
sidebar_position: 1
---

# PgBackrest

This PostgreSQL container image includes pgBackRest, a reliable and efficient backup and restore solution for PostgreSQL. pgBackRest is built to handle large databases and supports full, differential, and incremental backups, as well as point-in-time recovery.

The image also provides convenience scripts and sensible defaults to simplify using pgBackRest in container environments. To enable pgBackRest in your PostgreSQL container, you can use the following Docker Compose configuration:

```yaml
services:
  postgresql:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    ports:
      - "5434:5432"
    environment:
      POSTGRES_PASSWORD: secret
      // highlight-next-line
      PGBACKREST_ENABLE: "true"
    volumes:
      - ./.data:/usr/local/pgsql/data
      - ./.backup:/usr/local/pgsql/backup
```

## Enable Automatic Backup

We also provide an option to enable automatic backup scheduling using cron syntax. You can configure the automatic backup settings using the following environment variables:

```yaml
services:
  postgresql:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    environment:
      POSTGRES_PASSWORD: secret
      // highlight-start
      PGBACKREST_ENABLE: "true"
      PGBACKREST_AUTO_ENABLE: "true"
      PGBACKREST_AUTO_FULL_CRON: "00 00 * * *"
      PGBACKREST_AUTO_DIFF_CRON: "0 */6 * * *" # Optional: Adjust as needed
      PGBACKREST_AUTO_INCR_CRON: "*/15 * * * *" # Optional: Adjust as needed
      PGBACKREST_AUTO_TIMEZONE: "Asia/Jakarta" # Optional: Set your desired timezone
      // highlight-end
    volumes:
      - ./.data:/usr/local/pgsql/data
      - ./.backup:/usr/local/pgsql/backup
```

### Automatic Backup on Replicas

By default, automatic backups run only on the primary node. Set `PGBACKREST_AUTO_PRIMARY_ONLY: "false"` to allow replicas to run scheduled backups:

```yaml
services:
  postgresql-replica:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    environment:
      PGBACKREST_ENABLE: "true"
      PGBACKREST_AUTO_ENABLE: "true"
      // highlight-next-line
      PGBACKREST_AUTO_PRIMARY_ONLY: "false"  # Allow replicas to run scheduled backups
      PGBACKREST_AUTO_FULL_CRON: "00 02 * * *"
```

This is useful when:
- Primary has slow disk I/O and you want to offload backup to faster replicas
- Replicas have access to the backup repository
- Using standby backup mode (`PGBACKREST_BACKUP_STANDBY: "prefer"`)

## Backup Performance & Verification Options

You can fine-tune backup behavior with the following environment variables:

```yaml
services:
  postgresql:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    environment:
      POSTGRES_PASSWORD: secret
      PGBACKREST_ENABLE: "true"
      PGBACKREST_AUTO_ENABLE: "true"
      // highlight-start
      PGBACKREST_BACKUP_START_FAST: "true"      # Faster full backups with checkpoint
      PGBACKREST_BACKUP_STOP_AUTO: "true"      # Clean shutdown after backup
      PGBACKREST_BACKUP_VERIFY: "true"         # Verify backup after creation (default: true)
      PGBACKREST_PROCESS_MAX: "4"              # Parallel backup/restore threads
      // highlight-end
```

| Variable | Default | Description |
|----------|---------|-------------|
| `PGBACKREST_BACKUP_START_FAST` | `false` | Force a checkpoint before backup for faster completion |
| `PGBACKREST_BACKUP_STOP_AUTO` | `false` | Stop PostgreSQL automatically after backup |
| `PGBACKREST_BACKUP_VERIFY` | `true` | Verify backup after creation to ensure integrity |
| `PGBACKREST_PROCESS_MAX` | - | Number of parallel processes for backup/restore (auto-detected if not set) |

## Backup Storage Configuration

Configure how backups are stored and retained:

```yaml
services:
  postgresql:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    environment:
      POSTGRES_PASSWORD: secret
      PGBACKREST_ENABLE: "true"
      // highlight-start
      PGBACKREST_REPO_RETENTION_FULL_TYPE: "full"  # WAL retention type
      PGBACKREST_ARCHIVE_ASYNC: "true"             # Async WAL archiving for performance
      PGBACKREST_SPARSE_TABLE: "true"              # Faster backup on supported filesystems
      PGBACKREST_REPO_COMPRESSION: "zstd"          # Backup compression (gzip/lz4/zstd)
      // highlight-end
```

| Variable | Default | Description |
|----------|---------|-------------|
| `PGBACKREST_REPO_RETENTION_FULL_TYPE` | - | WAL retention type: `full` (full backups only) or `backup` (include differential WAL) |
| `PGBACKREST_ARCHIVE_ASYNC` | `false` | Enable async WAL archiving for better performance |
| `PGBACKREST_SPARSE_TABLE` | `false` | Enable sparse table support for faster backups on supported filesystems |
| `PGBACKREST_REPO_COMPRESSION` | `gzip` | Backup compression type: `gzip`, `lz4`, or `zstd` |

## Backup to S3-Compatible Storage

The image also supports backing up to S3-compatible storage solutions. You can configure the S3 repository settings using environment variables. Here's an example configuration for backing up to an S3-compatible service:

```yaml
services:
  postgresql:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    ports:
      - "5434:5432"
    environment:
      POSTGRES_PASSWORD: secret
      // highlight-start
      PGBACKREST_ENABLE: "true"
      PGBACKREST_REPO_TYPE: s3
      PGBACKREST_REPO_S3_BUCKET: test-pgbackrest
      PGBACKREST_REPO_S3_ENDPOINT: minio.example.com
      PGBACKREST_REPO_S3_REGION: id-jakarta-1
      PGBACKREST_REPO_S3_KEY: <access-key>
      PGBACKREST_REPO_S3_KEY_SECRET: <secret-key>
      PGBACKREST_REPO_S3_URI_STYLE: path
      PGBACKREST_REPO_PATH: /example # Optional: Path inside the bucket
      // highlight-end
      # Optional, can be combined with Auto Backup Configuration
      # PGBACKREST_AUTO_ENABLE: "true"
      # PGBACKREST_AUTO_FULL_CRON: "00 00 * * *"
      # PGBACKREST_AUTO_DIFF_CRON: "0 */6 * * *"
      # PGBACKREST_AUTO_INCR_CRON: "*/15 * * * *"
      # PGBACKREST_AUTO_TIMEZONE: "Asia/Jakarta"
    volumes:
      - ./.data:/usr/local/pgsql/data
      - ./.backup:/usr/local/pgsql/backup
```

### S3 Extended Options

Additional S3 configuration options:

```yaml
services:
  postgresql:
    environment:
      # ... base S3 config ...
      // highlight-start
      PGBACKREST_REPO_S3_BUCKET_REGION: "ap-indonesia-1"  # Explicit bucket region
      PGBACKREST_REPO_S3_ENDPOINT_FLEXIBLE: "true"        # Allow flexible endpoint matching
      PGBACKREST_REPO_S3_VERIFY_TLS: "true"               # Verify TLS certificates
      PGBACKREST_REPO_S3_CA_FILE: "/path/to/ca-bundle.crt"  # Custom CA bundle file
      PGBACKREST_REPO_S3_CA_PATH: "/path/to/ca-bundle/"   # Custom CA bundle path
      // highlight-end
```

| Variable | Description |
|----------|-------------|
| `PGBACKREST_REPO_S3_BUCKET_REGION` | Explicit S3 bucket region (useful for cross-region access) |
| `PGBACKREST_REPO_S3_ENDPOINT_FLEXIBLE` | Enable flexible endpoint matching for S3-compatible providers |
| `PGBACKREST_REPO_S3_VERIFY_TLS` | Verify TLS certificates (default: true) |
| `PGBACKREST_REPO_S3_CA_FILE` | Path to CA bundle file for TLS verification |
| `PGBACKREST_REPO_S3_CA_PATH` | Path to CA bundle directory for TLS verification |

## Backup to SFTP Server

The image also supports backing up to an SFTP server. You can configure the SFTP repository settings using environment variables. Here's an example configuration for backing up to an SFTP server:

```yaml
services:
  postgresql:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    ports:
      - "5434:5432"
    environment:
      POSTGRES_PASSWORD: secret
      // highlight-start
      PGBACKREST_ENABLE: "true"
      PGBACKREST_REPO_TYPE: sftp
      PGBACKREST_REPO_SFTP_HOST: 10.11.12.1
      PGBACKREST_REPO_SFTP_HOST_PORT: 22
      PGBACKREST_REPO_SFTP_HOST_USER: devops
      PGBACKREST_REPO_SFTP_PRIVATE_KEY_FILE: /home/postgres/.ssh/id_ed25519
      PGBACKREST_REPO_SFTP_PUBLIC_KEY_FILE: /home/postgres/.ssh/id_ed25519.pub
      PGBACKREST_REPO_PATH: /home/devops/pgbackrest
      PGBACKREST_REPO_SFTP_HOST_KEY_HASH_TYPE: sha256
      PGBACKREST_REPO_SFTP_HOST_KEY_CHECK_TYPE: none
      // highlight-end
      # Optional, can be combined with Auto Backup Configuration
      # PGBACKREST_AUTO_ENABLE: "true"
      # PGBACKREST_AUTO_FULL_CRON: "00 00 * * *"
      # PGBACKREST_AUTO_DIFF_CRON: "0 */6 * * *"
      # PGBACKREST_AUTO_INCR_CRON: "*/15 * * * *"
      # PGBACKREST_AUTO_TIMEZONE: "Asia/Jakarta"
    volumes:
      - ./.data:/usr/local/pgsql/data
      - ./.backup:/usr/local/pgsql/backup
      - /home/<user>/.ssh/id_ed25519:/home/postgres/.ssh/id_ed25519:ro
      - /home/<user>/.ssh/id_ed25519.pub:/home/postgres/.ssh/id_ed25519.pub:ro
```

## Backup to GCS (Google Cloud Storage)

The image also supports backing up to Google Cloud Storage:

```yaml
services:
  postgresql:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    ports:
      - "5434:5432"
    environment:
      POSTGRES_PASSWORD: secret
      // highlight-start
      PGBACKREST_ENABLE: "true"
      PGBACKREST_REPO_TYPE: gcs
      PGBACKREST_REPO_GCS_BUCKET: my-pgbackrest-bucket
      PGBACKREST_REPO_GCS_ENDPOINT: storage.googleapis.com
      PGBACKREST_REPO_GCS_KEY: <service-account-key>
      // highlight-end
      # Optional GCS settings
      # PGBACKREST_REPO_GCS_ENDPOINT_FLEXIBLE: "true"
      # PGBACKREST_REPO_GCS_CA_BUNDLE_FILE: "/path/to/ca-bundle.crt"
      # PGBACKREST_REPO_GCS_CA_BUNDLE_PATH: "/path/to/ca-bundle/"
    volumes:
      - ./.data:/usr/local/pgsql/data
      - ./.backup:/usr/local/pgsql/backup
```

### GCS Configuration Options

| Variable | Description |
|----------|-------------|
| `PGBACKREST_REPO_GCS_BUCKET` | GCS bucket name |
| `PGBACKREST_REPO_GCS_ENDPOINT` | GCS endpoint (default: storage.googleapis.com) |
| `PGBACKREST_REPO_GCS_KEY` | Service account JSON key |
| `PGBACKREST_REPO_GCS_KEY_TYPE` | Key type (default: service-account) |
| `PGBACKREST_REPO_GCS_USER_PROJECT` | GCP project ID for requester-pays buckets |
| `PGBACKREST_REPO_GCS_ENDPOINT_FLEXIBLE` | Enable flexible endpoint matching |
| `PGBACKREST_REPO_GCS_CA_BUNDLE_FILE` | CA bundle file for TLS verification |
| `PGBACKREST_REPO_GCS_CA_BUNDLE_PATH` | CA bundle directory for TLS verification |

## Standby Backup via SSH

For environments with SSH access between replicas and the primary, you can configure standby backup to reduce load on the primary. This allows replicas to perform backups directly without impacting the primary's performance.

:::info
**No sidecar container required!** The PostgreSQL container includes built-in SSHD that runs automatically on the primary when standby backup is configured. SSH keys are auto-generated and shared via a volume.
:::

### Requirements

| Requirement | Description |
|-------------|-------------|
| SSH access | Passwordless SSH from replica to primary |
| Primary path | `PGBACKREST_PRIMARY_PATH` must point to primary's data directory |
| Shared SSH volume | Replicas mount the same SSH volume as primary to access keys |

### Built-in SSHD

The PostgreSQL container automatically runs an SSHD daemon on the primary node when standby backup is configured. This eliminates the need for a separate SSHD sidecar container.

**How it works:**
- SSH host keys are generated automatically on first startup
- SSH user keys (for replica authentication) are generated and added to `authorized_keys`
- Replicas mount the same volume (`/home/postgres/.ssh`) to access the keys
- SSHD starts automatically at runtime on primary nodes

**Volume configuration required:**
```yaml
volumes:
  - postgresql_primary_ssh:/home/postgres/.ssh  # Shared between primary and replicas
```

### SSH Configuration Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `PGBACKREST_PRIMARY_HOST` | - | Primary hostname (required for standby backup) |
| `PGBACKREST_PRIMARY_PORT` | `5432` | Primary PostgreSQL port |
| `PGBACKREST_PRIMARY_PATH` | - | Primary data directory path (required for standby backup) |
| `PGBACKREST_PRIMARY_USER` | `postgres` | User for primary connection |
| `PGBACKREST_PRIMARY_SSH_PORT` | `22` | SSH port for primary |
| `PGBACKREST_PRIMARY_SSH_USER` | `postgres` | SSH user for primary |
| `PGBACKREST_PRIMARY_SSH_KEY_FILE` | `/home/postgres/.ssh/id_rsa` | SSH private key file |
| `PGBACKREST_PRIMARY_SSH_STRICT_HOST_KEY_CHECKING` | `yes` | Enable strict host key checking |
| `PGBACKREST_PRIMARY_SSH_KNOWN_HOSTS` | - | Path to known_hosts file |

For development/testing, disable strict host key checking:
```yaml
PGBACKREST_PRIMARY_SSH_STRICT_HOST_KEY_CHECKING: "no"
```

### Backup Standby Modes

| Mode | Value | Description |
|------|-------|-------------|
| **Required** | `y`, `yes`, `1`, `on`, `true` | Backup **must** run from standby; fails if unavailable |
| **Preferred** | `prefer` | Backup **from standby if available**, otherwise fallback to primary |
| **Disabled** | `n`, `no`, `0`, `off`, `false` | Backup from primary only |

```yaml
services:
  postgresql-primary:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    ports:
      - "5432:5432"
      - "2222:22"  # SSH port (optional, for external access)
    environment:
      POSTGRES_PASSWORD: secret
      PGBACKREST_ENABLE: "true"
      PGBACKREST_PRIMARY_PATH: /usr/local/pgsql/data
      PGBACKREST_BACKUP_STANDBY: "prefer"
    volumes:
      - ./.data:/usr/local/pgsql/data
      - ./.backup:/usr/local/pgsql/backup
      - postgresql_ssh:/home/postgres/.ssh  # Shared SSH volume

  postgresql-replica:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    ports:
      - "5433:5432"
    environment:
      REPLICATION_ROLE: replica
      PRIMARY_HOST: postgresql-primary
      PGBACKREST_ENABLE: "true"
      PGBACKREST_PRIMARY_PATH: /usr/local/pgsql/data
      PGBACKREST_PRIMARY_HOST: postgresql-primary
      PGBACKREST_BACKUP_STANDBY: "y"
    volumes:
      - ./.data:/usr/local/pgsql/data
      - ./.backup:/usr/local/pgsql/backup
      - postgresql_ssh:/home/postgres/.ssh  # Mount same SSH volume

volumes:
  postgresql_ssh:
```

For a complete working example, see [compose.native-ha.backup-standby.yaml](https://github.com/supanadit/container-examples/blob/main/postgresql/compose.native-ha.backup-standby.yaml).

### Kubernetes Limitations

:::warning
Standby backup (`backup-standby=y` or `prefer`) **does not work** in Kubernetes due to:

- **No SSH between pods** - Pods cannot SSH to each other
- **Pod isolation** - Each PostgreSQL runs on its own node with no network access to other pods

In K8s environments, always set `PGBACKREST_BACKUP_STANDBY: "n"` or leave it unset (defaults to primary-only backup).
:::

For Kubernetes, use primary-only backup to S3:

```yaml
services:
  postgresql:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    environment:
      POSTGRES_PASSWORD: secret
      PGBACKREST_ENABLE: "true"
      PGBACKREST_BACKUP_STANDBY: "n"  # Explicitly disable for K8s
      PGBACKREST_REPO_TYPE: s3
      PGBACKREST_REPO_S3_BUCKET: my-backups
      PGBACKREST_REPO_S3_ENDPOINT: s3.amazonaws.com
      # ... S3 credentials
```

## Boolean Value Formats

All pgBackRest-related environment variables that accept boolean values accept the following formats (case-insensitive):

| Value | Description |
|-------|-------------|
| `true`, `1`, `yes`, `on`, `y` | Truthy values |
| `false`, `0`, `no`, `off`, `n` | Falsy values |

For example, all of the following are equivalent:

```yaml
environment:
  PGBACKREST_ENABLE: "true"    # ✓
  PGBACKREST_ENABLE: "1"       # ✓ also truthy
  PGBACKREST_ENABLE: "yes"     # ✓ also truthy
  PGBACKREST_ENABLE: "on"      # ✓ also truthy
  PGBACKREST_ENABLE: "y"       # ✓ also truthy
```

## PGBACKREST_ENABLE Precedence

`PGBACKREST_ENABLE` acts as a master switch. When set to `false`, all related pgBackRest settings are silently ignored even if they are configured:

| Setting | Behavior when PGBACKREST_ENABLE=false |
|---------|---------------------------------------|
| `PGBACKREST_AUTO_ENABLE` | Ignored - automatic backup scheduling is disabled |
| `PGBACKREST_RESTORE` | Ignored - restore operations are disabled |
| `PGBACKREST_ARCHIVE_ENABLE` | Ignored - WAL archiving is disabled |

This design prevents accidental backup/restore operations when you have disabled pgBackRest but forgot to unset related variables.

### Warning Messages

When contradictions are detected, the container logs warnings (not errors) to help you identify misconfigurations:

```
WARNING: PGBACKREST_ENABLE is false but PGBACKREST_AUTO_ENABLE is true - ignoring auto backup settings
WARNING: PGBACKREST_ENABLE is false but PGBACKREST_RESTORE is true - ignoring restore settings
```

## Point-in-Time Recovery (PITR)

The container supports comprehensive Point-in-Time Recovery options. Configure restore targets using environment variables:

```yaml
services:
  postgresql:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    environment:
      POSTGRES_PASSWORD: secret
      PGBACKREST_ENABLE: "true"
      PGBACKREST_STANZA: default
      // highlight-start
      # PITR Target Options (choose one or more)
      PGBACKREST_RESTORE_TARGET_IMMEDIATE: "true"           # Stop at immediate consistency point
      # PGBACKREST_RESTORE_TARGET_XID: "12345"              # Transaction ID
      # PGBACKREST_RESTORE_TARGET_LSN: "0/3000000"          # Log Sequence Number
      # PGBACKREST_RESTORE_TARGET_NAME: "my_restore_point"  # Named restore point
      # PGBACKREST_RESTORE_TARGET: "2025-01-15 14:30:00+07" # Timestamp target
      # PGBACKREST_RESTORE_TARGET_TIMELINE: "latest"        # Timeline
      # PGBACKREST_RESTORE_TARGET_ACTION: "pause"           # Action at target: pause/promote/shutdown
      # PGBACKREST_RESTORE_RECOVERY_TARGET_TLI: "1"         # Recovery target timeline
      # PGBACKREST_RESTORE_RECOVERY_TARGET_ACTION: "promote" # Action at recovery target
      // highlight-end
      # Additional restore options
      PGBACKREST_RESTORE_TYPE: "name"        # Restore type: name, time, xid, lsn, immediate
      PGBACKREST_RESTORE_DELTA: "true"       # Use checksum-based delta restore
      PGBACKREST_RESTORE_FORCE: "false"      # Force restore operation
      PGBACKREST_RESTORE_SET: "latest"      # Select specific backup set
      # PGBACKREST_RESTORE_TABLESPACE_MAP: "old_dir=/new_dir,ts2=/mnt/ts"  # Remap tablespaces
    volumes:
      - ./.data:/usr/local/pgsql/data
      - ./.backup:/usr/local/pgsql/backup
```

### PITR Configuration Options

| Variable | Description |
|----------|-------------|
| `PGBACKREST_RESTORE_TARGET` | Recovery target time (timestamp) |
| `PGBACKREST_RESTORE_TARGET_XID` | Recovery target transaction ID |
| `PGBACKREST_RESTORE_TARGET_LSN` | Recovery target LSN (Log Sequence Number) |
| `PGBACKREST_RESTORE_TARGET_NAME` | Named restore point to recover to |
| `PGBACKREST_RESTORE_TARGET_IMMEDIATE` | Stop at immediate consistency point |
| `PGBACKREST_RESTORE_TARGET_TIMELINE` | Timeline to recover to (e.g., `latest`) |
| `PGBACKREST_RESTORE_TARGET_ACTION` | Action at target: `pause`, `promote`, `shutdown` |
| `PGBACKREST_RESTORE_RECOVERY_TARGET_TLI` | Recovery target timeline ID |
| `PGBACKREST_RESTORE_RECOVERY_TARGET_ACTION` | Recovery target action: `pause`, `promote`, `shutdown` |
| `PGBACKREST_RESTORE_TYPE` | Restore type: `name`, `time`, `xid`, `lsn`, `immediate` |
| `PGBACKREST_RESTORE_DELTA` | Use checksum-based delta restore (default: true) |
| `PGBACKREST_RESTORE_FORCE` | Force restore, bypassing safety checks |
| `PGBACKREST_RESTORE_SET` | Select specific backup set (e.g., `latest`) |
| `PGBACKREST_RESTORE_TABLESPACE_MAP` | Remap tablespaces during restore (format: `old_dir=/new_dir`) |

### Example: Restore to Named Restore Point

```yaml
services:
  postgresql:
    environment:
      PGBACKREST_ENABLE: "true"
      PGBACKREST_RESTORE_TARGET_NAME: "before_major_change"
      PGBACKREST_RESTORE_TYPE: "name"
```

### Example: Restore to Specific Timestamp

```yaml
services:
  postgresql:
    environment:
      PGBACKREST_ENABLE: "true"
      PGBACKREST_RESTORE_TARGET: "2025-01-15 14:30:00+07"
      PGBACKREST_RESTORE_TYPE: "time"
      PGBACKREST_RESTORE_TARGET_ACTION: "promote"
```

### Example: Immediate Recovery with Tablespace Remapping

```yaml
services:
  postgresql:
    environment:
      PGBACKREST_ENABLE: "true"
      PGBACKREST_RESTORE_TARGET_IMMEDIATE: "true"
      PGBACKREST_RESTORE_TABLESPACE_MAP: "/old/ts1=/mnt/storage/ts1,/old/ts2=/mnt/storage/ts2"
```

## Patroni Integration

When using Patroni for HA management, pgBackRest automatically integrates via Patroni callbacks. This ensures pgBackRest configuration is updated when nodes change roles during failover or switchover.

### How It Works

| Event | Action |
|-------|--------|
| Replica promoted to primary | Remove pg2-host settings, set `backup-standby=n` |
| Primary demoted to replica | Add pg2-host settings (if `PGBACKREST_PRIMARY_PATH` set) |
| Backup completes | Log status to container logs |
| Restore completes | Regenerate pgBackRest config for current role |

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `PATRONI_PGBACKREST_CALLBACKS` | `true` | Enable/disable Patroni callbacks |

### Example Compose Configuration

```yaml
services:
  postgresql:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    environment:
      HA_MODE: patroni
      PATRONI_NAME: postgres-${HOSTNAME}
      PATRONI_SCOPE: postgres-cluster
      ETCD_HOSTS: etcd:2379
      POSTGRES_PASSWORD: secret
      PGBACKREST_ENABLE: "true"
      PGBACKREST_STANZA: default
      PGBACKREST_REPO_TYPE: s3
      PGBACKREST_REPO_S3_BUCKET: pgbackrest
      PGBACKREST_REPO_S3_ENDPOINT: minio:9000
      PGBACKREST_REPO_S3_KEY: superadmin
      PGBACKREST_REPO_S3_KEY_SECRET: supersecretpassword
      PGBACKREST_AUTO_ENABLE: "true"
      PGBACKREST_AUTO_FULL_CRON: "00 02 * * *"
      PATRONI_PGBACKREST_CALLBACKS: "true"  # Default, but explicit
    volumes:
      - postgresql_data:/usr/local/pgsql/data
      - pgbackrest_backup:/usr/local/pgsql/backup
```

### Limitations

- Callbacks require Patroni HA mode (`HA_MODE: patroni`)
- Standby backup (`pg2-host`) requires SSH access to primary
- Not compatible with Kubernetes (no SSH between pods)
- For Kubernetes, use `PGBACKREST_BACKUP_STANDBY: "n"` with S3 backup

## Environment Variables Reference

### General Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `PGBACKREST_ENABLE` | `false` | Enable pgBackRest backup/restore |
| `PGBACKREST_STANZA` | `default` | pgBackRest stanza name |
| `PGBACKREST_PASSWORD` | - | PostgreSQL password for pgBackRest connection |

### Automatic Backup

| Variable | Default | Description |
|----------|---------|-------------|
| `PGBACKREST_AUTO_ENABLE` | `false` | Enable automatic backup scheduling |
| `PGBACKREST_AUTO_FULL_CRON` | `0 2 * * *` | Full backup cron schedule |
| `PGBACKREST_AUTO_DIFF_CRON` | `20 2,8,14,20 * * *` | Differential backup cron schedule |
| `PGBACKREST_AUTO_INCR_CRON` | `*/15 * * * *` | Incremental backup cron schedule |
| `PGBACKREST_AUTO_TIMEZONE` | `UTC` | Timezone for cron schedules |
| `PGBACKREST_AUTO_PRIMARY_ONLY` | `true` | Only run backups on primary |
| `PGBACKREST_AUTO_FIRST_INCR_DELAY` | `120` | Delay before first incremental backup (seconds) |

### Backup Performance & Verification

| Variable | Default | Description |
|----------|---------|-------------|
| `PGBACKREST_BACKUP_START_FAST` | `false` | Force checkpoint before backup |
| `PGBACKREST_BACKUP_STOP_AUTO` | `false` | Stop PostgreSQL after backup |
| `PGBACKREST_BACKUP_VERIFY` | `true` | Verify backup after creation |
| `PGBACKREST_BACKUP_CHECKSUM_PAGE` | `false` | Validate data page checksums during backup |
| `PGBACKREST_BACKUP_ARCHIVE_CHECK` | `true` | Check WAL segments in archive |
| `PGBACKREST_BACKUP_ARCHIVE_COPY` | `false` | Copy WAL segments for backup consistency |
| `PGBACKREST_BACKUP_EXPIRE_AUTO` | `false` | Auto-run expire after backup |
| `PGBACKREST_BACKUP_RESUME` | `true` | Allow resuming failed backup |
| `PGBACKREST_BACKUP_EXCLUDE` | - | Exclude paths from backup (comma-separated) |
| `PGBACKREST_BACKUP_ANNOTATION` | - | Backup annotations (format: key=value,key2=value2) |
| `PGBACKREST_PROCESS_MAX` | - | Parallel processes for backup/restore |
| `PGBACKREST_BACKUP_STANDBY` | - | Backup from standby: `y`, `prefer`, `n` |

### Storage & Retention

| Variable | Default | Description |
|----------|---------|-------------|
| `PGBACKREST_REPO_TYPE` | `posix` | Repository type: `posix`, `s3`, `gcs`, `sftp`, `azure` |
| `PGBACKREST_REPO_PATH` | - | Repository path override |
| `PGBACKREST_REPO_RETENTION_FULL` | `2` | Full backup retention count |
| `PGBACKREST_REPO_RETENTION_DIFF` | `6` | Differential backup retention count |
| `PGBACKREST_REPO_RETENTION_FULL_TYPE` | - | Retention type: `full` or `backup` |
| `PGBACKREST_REPO_RETENTION_ARCHIVE` | - | WAL archive retention count |
| `PGBACKREST_REPO_RETENTION_ARCHIVE_TYPE` | - | WAL retention type: `full`, `diff`, `incr` |
| `PGBACKREST_REPO_HARDLINK` | `false` | Create hardlinks for local backups |
| `PGBACKREST_REPO_SYMLINK` | `false` | Create symlinks in repository |
| `PGBACKREST_REPO_CIPHER_TYPE` | - | Encryption cipher: `none`, `aes-256-cbc` |
| `PGBACKREST_REPO_CIPHER_PASS` | - | Encryption passphrase |
| `PGBACKREST_ARCHIVE_ASYNC` | `false` | Enable async WAL archiving |
| `PGBACKREST_SPARSE_TABLE` | `false` | Enable sparse table support |
| `PGBACKREST_REPO_COMPRESSION` | `gzip` | Compression: `gzip`, `lz4`, `zstd` |
| `PGBACKREST_COMPRESS_LEVEL` | - | Compression level (0-9 for gzip, 0-19 for zstd) |
| `PGBACKREST_COMPRESS_LEVEL_NETWORK` | - | Network compression level |

### Performance & Tuning

| Variable | Default | Description |
|----------|---------|-------------|
| `PGBACKREST_BUFFER_SIZE` | - | Buffer size for I/O (16KiB-16MiB) |
| `PGBACKREST_IO_TIMEOUT` | - | I/O timeout |
| `PGBACKREST_DB_TIMEOUT` | - | Database query timeout |
| `PGBACKREST_PROTOCOL_TIMEOUT` | - | Protocol timeout |
| `PGBACKREST_ARCHIVE_GET_QUEUE_MAX` | - | Max size of archive-get queue |
| `PGBACKREST_ARCHIVE_PUSH_QUEUE_MAX` | - | Max size of archive-push queue |
| `PGBACKREST_ARCHIVE_TIMEOUT` | - | Archive command timeout |
| `PGBACKREST_TCP_KEEP_ALIVE_COUNT` | - | TCP keep-alive count |
| `PGBACKREST_TCP_KEEP_ALIVE_IDLE` | - | TCP keep-alive idle time |
| `PGBACKREST_TCP_KEEP_ALIVE_INTERVAL` | - | TCP keep-alive interval |
| `PGBACKREST_SCK_KEEP_ALIVE` | `false` | Enable socket keep-alive |
| `PGBACKREST_NEUTRAL_UMASK` | `false` | Use neutral umask for files |
| `PGBACKREST_MANIFEST_SAVE_THRESHOLD` | - | Manifest save threshold |

### S3 Configuration

| Variable | Description |
|----------|-------------|
| `PGBACKREST_REPO_S3_BUCKET` | S3 bucket name |
| `PGBACKREST_REPO_S3_ENDPOINT` | S3 endpoint URL |
| `PGBACKREST_REPO_S3_REGION` | S3 region |
| `PGBACKREST_REPO_S3_KEY` | S3 access key |
| `PGBACKREST_REPO_S3_KEY_SECRET` | S3 secret key |
| `PGBACKREST_REPO_S3_BUCKET_REGION` | Explicit bucket region |
| `PGBACKREST_REPO_S3_ENDPOINT_FLEXIBLE` | Flexible endpoint matching |
| `PGBACKREST_REPO_S3_URI_STYLE` | URI style: `path` or `host` |
| `PGBACKREST_REPO_S3_PORT` | S3 port |
| `PGBACKREST_REPO_S3_VERIFY_TLS` | Verify TLS certificates |
| `PGBACKREST_REPO_S3_CA_FILE` | CA bundle file |
| `PGBACKREST_REPO_S3_CA_PATH` | CA bundle path |
| `PGBACKREST_REPO_S3_STORAGE_CLASS` | Storage class |
| `PGBACKREST_REPO_S3_TOKEN` | STS token |

### GCS Configuration

| Variable | Description |
|----------|-------------|
| `PGBACKREST_REPO_GCS_BUCKET` | GCS bucket name |
| `PGBACKREST_REPO_GCS_ENDPOINT` | GCS endpoint |
| `PGBACKREST_REPO_GCS_KEY` | Service account key |
| `PGBACKREST_REPO_GCS_KEY_TYPE` | Key type |
| `PGBACKREST_REPO_GCS_USER_PROJECT` | GCP project for requester-pays |
| `PGBACKREST_REPO_GCS_ENDPOINT_FLEXIBLE` | Flexible endpoint matching |
| `PGBACKREST_REPO_GCS_CA_BUNDLE_FILE` | CA bundle file |
| `PGBACKREST_REPO_GCS_CA_BUNDLE_PATH` | CA bundle path |

### SFTP Configuration

| Variable | Description |
|----------|-------------|
| `PGBACKREST_REPO_SFTP_HOST` | SFTP host |
| `PGBACKREST_REPO_SFTP_HOST_PORT` | SFTP port |
| `PGBACKREST_REPO_SFTP_HOST_USER` | SFTP user |
| `PGBACKREST_REPO_SFTP_PRIVATE_KEY_FILE` | Private key path |
| `PGBACKREST_REPO_SFTP_PUBLIC_KEY_FILE` | Public key path |
| `PGBACKREST_REPO_SFTP_HOST_KEY_HASH_TYPE` | Host key hash type |
| `PGBACKREST_REPO_SFTP_HOST_KEY_CHECK_TYPE` | Host key check type |
| `PGBACKREST_REPO_SFTP_KNOWN_HOSTS` | Known hosts file |
| `PGBACKREST_REPO_SFTP_PRIVATE_KEY_PASSPHRASE` | Key passphrase |

### Standby Backup (SSH)

| Variable | Default | Description |
|----------|---------|-------------|
| `PGBACKREST_PRIMARY_HOST` | - | Primary hostname |
| `PGBACKREST_PRIMARY_PORT` | `5432` | Primary PostgreSQL port |
| `PGBACKREST_PRIMARY_PATH` | - | Primary data directory |
| `PGBACKREST_PRIMARY_USER` | `postgres` | Primary user |
| `PGBACKREST_PRIMARY_SSH_PORT` | `22` | Primary SSH port |
| `PGBACKREST_PRIMARY_SSH_USER` | `postgres` | Primary SSH user |
| `PGBACKREST_PRIMARY_SSH_KEY_FILE` | `/home/postgres/.ssh/id_rsa` | SSH key file |
| `PGBACKREST_PRIMARY_SSH_STRICT_HOST_KEY_CHECKING` | `yes` | Strict host key checking |

### Point-in-Time Recovery

| Variable | Description |
|----------|-------------|
| `PGBACKREST_RESTORE_TYPE` | Restore type: `name`, `time`, `xid`, `lsn`, `immediate` |
| `PGBACKREST_RESTORE_TARGET` | Recovery target timestamp |
| `PGBACKREST_RESTORE_TARGET_XID` | Recovery target transaction ID |
| `PGBACKREST_RESTORE_TARGET_LSN` | Recovery target LSN |
| `PGBACKREST_RESTORE_TARGET_NAME` | Named restore point |
| `PGBACKREST_RESTORE_TARGET_IMMEDIATE` | Stop at immediate consistency |
| `PGBACKREST_RESTORE_TARGET_TIMELINE` | Target timeline |
| `PGBACKREST_RESTORE_TARGET_ACTION` | Action at target: `pause`, `promote`, `shutdown` |
| `PGBACKREST_RESTORE_RECOVERY_TARGET_TLI` | Recovery target timeline ID |
| `PGBACKREST_RESTORE_RECOVERY_TARGET_ACTION` | Recovery target action |
| `PGBACKREST_RESTORE_ARCHIVE_MODE` | Archive mode on restore: `off`, `on`, `always` |
| `PGBACKREST_RESTORE_LINK_ALL` | Restore all symlinks |
| `PGBACKREST_RESTORE_LINK_MAP` | Modify symlink destinations (format: `old=/new`) |
| `PGBACKREST_RESTORE_DB_INCLUDE` | Include only specified databases (comma-separated) |
| `PGBACKREST_RESTORE_DB_EXCLUDE` | Exclude databases from restore (comma-separated) |
| `PGBACKREST_RESTORE_RECOVERY_OPTION` | Set postgresql.auto.conf options (format: `key=value`) |
| `PGBACKREST_RESTORE_DELTA` | Use delta restore (checksum-based) |
| `PGBACKREST_RESTORE_FORCE` | Force restore operation |
| `PGBACKREST_RESTORE_SET` | Select specific backup set |
| `PGBACKREST_RESTORE_TABLESPACE_MAP` | Tablespace remapping (format: `old=/new`) |
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

## Restore Command Options

When performing restores, the following pgBackRest command-line options are available regardless of `PGBACKREST_ENABLE` setting:

| Option | Description |
|--------|-------------|
| `delta` | Restore using checksum differences instead of timestamp |
| `force` | Force restore operation, bypassing safety checks |

These are pgBackRest CLI options and work independently of the container's ENABLE setting. For example:

```yaml
environment:
  POSTGRES_PASSWORD: secret
  PGBACKREST_ENABLE: "false"  # Backup disabled
  PGBACKREST_RESTORE_DELTA: "true"  # But delta restore still works when needed
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
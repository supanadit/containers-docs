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
    ports:
      - "5434:5432"
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
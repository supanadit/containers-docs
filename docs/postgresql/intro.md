---
id: postgresql-intro-doc
title: PostgreSQL
---

:::warning Beta
This PostgreSQL container image is currently in beta. Most of the features are stable, but some features might still be under development or testing. Use with caution in production environments.
:::

# PostgreSQL

This is a documentation page about PostgreSQL container. To quickstart the PostgreSQL container with Docker compose, you can use the following code snippet:

```yaml
services:
  postgresql:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    ports:
      - "5434:5432"
    environment:
      POSTGRES_PASSWORD: secret
    volumes:
      - ./.data:/usr/local/pgsql/data
```


## High Availability Options

This PostgreSQL container supports multiple high availability configurations:

### Native HA

Lightweight streaming replication without external dependencies:

- **Single Sync Mode** - One synchronous replica with automatic failover backup
- **Quorum Sync Mode** - Multiple replicas must acknowledge commits
- **Async Mode** - Best performance, no commit waiting
- **Dynamic Configuration** - Change sync settings at runtime without restart

See [Native HA Documentation](./native_ha/intro) for details.

### Patroni

Distributed high availability with automatic failover:

- Requires etcd, Consul, or ZooKeeper
- Automatic leader election
- Distributed consensus

See [Patroni Documentation](./patroni) for details.

### Patroni + Citus

Distributed PostgreSQL with Citus for horizontal scaling:

- Citus distributes tables across multiple nodes
- Patroni manages HA for each node

See [Patroni Citus Documentation](./patroni_citus) for details.

## Important Notes

We only support PostgreSQL versions that are actively maintained by the official PostgreSQL team. Please refer to the [major version support policy](https://www.postgresql.org/support/versioning/) for details. It is recommended to use a specific major version tag to avoid unexpected issues during minor version upgrades.

If you need deprecated major versions, please check our older tags or build from the corresponding Dockerfile in the GitHub repository. We also provide commercial support for legacy version; such as migration assistance and security patches. Please contact us for more information.

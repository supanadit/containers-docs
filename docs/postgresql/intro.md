---
id: postgresql-intro-doc
slug: /postgresql
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


## Important Notes

We only support PostgreSQL versions that are actively maintained by the official PostgreSQL team. Please refer to the [major version support policy](https://www.postgresql.org/support/versioning/) for details. It is recommended to use a specific major version tag to avoid unexpected issues during minor version upgrades.

If you need deprecated major versions, please check our older tags or build from the corresponding Dockerfile in the GitHub repository. We also provide commercial support for legacy version; such as migration assistance and security patches. Please contact us for more information.

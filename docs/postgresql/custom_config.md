# Custom Configuration

You don't need to modify the PostgreSQL configuration files directly. Instead, you can set custom configuration parameters using environment variables in your Docker Compose file. The format for these environment variables is `POSTGRESQL_CONFIG_<PARAMETER_NAME>`, where `<PARAMETER_NAME>` is the name of the PostgreSQL configuration parameter you want to set, converted to uppercase and with dots replaced by underscores. For example, to set the `max_connections` and `shared_buffers` parameters, you can use the following Docker Compose configuration:

```yaml
services:
  postgresql:
    image: ghcr.io/supanadit/containers/postgresql:17.6-r0.0.19
    ports:
      - "5434:5432"
    environment:
      POSTGRES_PASSWORD: secret
      POSTGRESQL_CONFIG_MAX_CONNECTIONS: "2000"
      POSTGRESQL_CONFIG_SHARED_BUFFERS: 16GB
      POSTGRESQL_CONFIG_EFFECTIVE_CACHE_SIZE: 32GB
    volumes:
      - ./.data:/usr/local/pgsql/data
```

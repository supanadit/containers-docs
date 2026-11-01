# Health & readiness

Every container runs a shared health server inside the image, registered by
the entrypoint before the application starts. Each application adds its own
protocol-aware probes, and a **composite** endpoint answers only when every
probe reports ready.

## Endpoints

| Endpoint      | Meaning                                            |
| ------------- | -------------------------------------------------- |
| `/health`     | Composite status: healthy only when all probes are |
| `/health/app` | The application's own probe (protocol-specific)    |

The address is configurable via `EZX_HEALTH_ADDR`. If it is unset, the app
still starts — health registration logs a warning instead of failing.

> [!NOTE]
> Always point orchestrators and compose `healthcheck` blocks at `/health`,
> not at a TCP port check. The composite endpoint verifies the app actually
> answers on its own protocol — a TCP handshake proves nothing.

## Example

Compose health check for PostgreSQL:

```yaml
healthcheck:
  test: ["CMD", "wget", "-qO-", "http://127.0.0.1:5432/health"]
  interval: 10s
  timeout: 5s
  retries: 5
```

The exact port matches the application's listening port; check the container
page for details. The test suites in the repository assert that `/health`
reports healthy and that the app runs as a non-root user — so a passing CI
build is a passing health check in practice.

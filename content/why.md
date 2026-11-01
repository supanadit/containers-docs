# Why containers

This project is built from personal experience running containers — both Docker
and Kubernetes — in production. Docker and Kubernetes are revolutionary, but
they still depend on the community to publish the images you actually run.

Most open-source programs officially ship containers, yet consistency is the
problem. Bitnami used to be the most consistent provider, but it changed its
publishing model: no more version pinning unless you move to `bitnami/legacy`,
which does not receive security updates. That change caused unexpected breaking
changes on personal servers and in professional environments alike.

So these images were built instead — modeled on what made Bitnami good, with
**extra sauce** on top:

- **Everything through environment variables.** Even the complicated
  configurations — Grafana Loki, Grafana Mimir, Thanos — reduce to plain env
  vars. No bespoke config languages to learn.
- **Declarative, reproducible builds.** Every image is assembled by
  [forge](https://github.com/supanadit/forge) from a `forge.toml` manifest:
  pinned downloads with checksums, explicit dependencies, automatic cleanup.
  No `setup.sh`, no shell entrypoints, no mystery layers.
- **Supervised runtime.** A single Go binary,
  [ezx](https://github.com/supanadit/ezx), stays PID 1: it forwards signals,
  reaps zombies, rotates logs, probes readiness, and drops privileges per
  process.
- **Power-user bundles.** PostgreSQL ships with a curated extension set,
  Kafka runs in KRaft mode with no ZooKeeper, WordPress is tuned for
  serverless OCI environments like Google Cloud Run.

The goal is simple: deploy robust systems with minimal effort, and be able to
read every layer you are running.

## License

The project is MIT licensed. Some bundled software (Grafana Loki, Mimir, MinIO)
carries dual licenses; the core of this project — Dockerfiles, entrypoints, and
the extra sauce — is fully open source.

## Feedback

These images are designed for general-purpose use out of the box, but the
scripts are open and customizable for specific use cases. Feedback, feature
requests, and bug reports are welcome — open a GitHub Issue to get started.

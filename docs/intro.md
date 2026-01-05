---
sidebar_position: 1
---

# Why

This project is based on my personal experience working as a DevOps Engineer. I encountered several challenges when I first began using containers—both Docker and Kubernetes—in production environments. While Docker and Kubernetes are revolutionary solutions for modern systems, they still rely on the community to provide content in the form of images.

Nowadays, most open-source programs officially support containers (primarily Docker), yet consistency remains an issue. Previously, Bitnami provided the most consistent images; however, they recently changed their publishing model. They no longer support version pinning, meaning users must either always use the `latest` tag or revert to `bitnami/legacy` for older versions, which do not receive security updates. This change caused unexpected breaking changes on my personal server as well as the professional environments I manage at work.

Consequently, I decided to build my own container images. I learned a great deal from existing providers—particularly Bitnami—but I have added **extra sauce** to make my custom builds even easier to use. Unlike Bitnami, which can have complex documentation and configurations for tools like Grafana Loki, Grafana Mimir, and Thanos, my images allow you to manage everything through simple environment variables.

This project also addresses specific power-user needs, such as PostgreSQL pre-bundled with various extensions, and a WordPress build optimized for serverless OCI-compatible environments like Google Cloud Run. My goal is to simplify the most complicated configurations, allowing users to deploy robust systems with minimal effort.

This project is licensed under the MIT License. While some included software—such as Grafana Loki, Mimir, and MinIO—may carry dual licenses, the core components of this project (including the Dockerfile, entrypoint.sh, and the 'extra sauce' scripts) are completely open source.

I have designed these images for general-purpose use, ensuring that both individuals and business entities can deploy them immediately without modification. However, the open-source nature of the scripts allows you to customize them for specific use cases if needed. I welcome feedback and am happy to review feature requests or bug reports—please feel free to open a GitHub Issue to get started."

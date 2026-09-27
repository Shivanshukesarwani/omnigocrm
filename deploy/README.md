# Production deployment

## Docker Compose

Set `OMNIGOCRM_DOMAIN`, `OMNIGOCRM_TLS_EMAIL`, `OMNIGOCRM_SITE_URL`, and validated `ESPOCRM_VERSION`/`POSTGRES_VERSION` values in `.env`. Create the two application secrets in `secrets/README.md`.

Validate and start:

```bash
docker compose -f docker-compose.prod.yml config
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml ps
```

Only ports 80/443 should be public. PostgreSQL is on an internal Docker network.

Run `scripts/backup-production.sh` regularly and copy backups to storage outside the server.

## Kubernetes

Build and publish an immutable image with the production-image GitHub Actions workflow. Replace the example domain and create the Kubernetes Secret through a secret manager. Ensure a StorageClass and TLS-capable Ingress controller exist, then run:

```bash
kubectl apply -k deploy/kubernetes
```

The included PostgreSQL StatefulSet is a single-node baseline. For high availability, use a managed PostgreSQL service or PostgreSQL operator with tested backups/failover.

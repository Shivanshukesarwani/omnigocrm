# Production Deployment

OmniGoCRM uses EspoCRM as its only CRM/data backend. This repository now includes a production Docker Compose baseline, HTTPS reverse proxy, Docker secrets, scheduled processing, backups, Kubernetes manifests, and a release image workflow.

EspoCRM recommends Docker Compose for production Docker deployments, supports Docker secrets through *_FILE variables, and recommends version-pinning when customizations are involved. citeturn0search0

## Production baseline

The recommended baseline is Docker Compose on a Linux VPS or dedicated server. EspoCRM currently supports PHP 8.3–8.5 with MySQL 8+ or MariaDB 10.3+. citeturn0search1

### Files

- `docker-compose.prod.yml` — production stack.
- `deploy/caddy/Caddyfile` — automatic HTTPS.
- `secrets/README.md` — secret creation.
- `scripts/backup-production.sh` — database and application-data backups.
- `deploy/kubernetes/` — Kubernetes baseline.
- `.github/workflows/production-image.yml` — GHCR release-image workflow.

## Docker Compose production

Create the environment file:

```bash
cp .env.example .env
```

Set the real domain, TLS email and site URL. Keep the validated EspoCRM and MariaDB versions pinned.

Create secrets:

```bash
mkdir -p secrets
openssl rand -base64 36 > secrets/db_password.txt
openssl rand -base64 36 > secrets/db_root_password.txt
openssl rand -base64 36 > secrets/admin_password.txt
chmod 600 secrets/*.txt
```

Validate:

```bash
docker compose -f docker-compose.prod.yml config
```

Start:

```bash
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml ps
```

Only ports 80/443 are published. MariaDB is isolated on an internal Docker network.

Caddy obtains and renews TLS certificates automatically when DNS points the domain at the server. EspoCRM documents Caddy as a supported Docker reverse-proxy approach. citeturn0search3

## Backups

Run:

```bash
./scripts/backup-production.sh
```

Store backups outside the production server too. Regularly test restoration.

## Updates

Do not deploy a floating `latest` image. Validate a specific EspoCRM release, build the OmniGoCRM image, run tests and migrations, then deploy the immutable version. EspoCRM documents version pinning as the way to handle incompatible customizations during upgrades. citeturn0search0

## Kubernetes

The Kubernetes baseline includes:

- Namespace
- ConfigMap
- Persistent storage
- MariaDB StatefulSet
- OmniGoCRM Deployment
- scheduled daemon
- Service
- TLS Ingress

The example Secret is documentation only and is intentionally excluded from Kustomize resources.

Create the real secret separately:

```bash
kubectl create namespace omnigocrm
kubectl -n omnigocrm create secret generic omnigocrm-secrets \
  --from-literal=db-password='REPLACE_ME' \
  --from-literal=db-root-password='REPLACE_ME' \
  --from-literal=admin-password='REPLACE_ME'
```

Replace `crm.example.com` in `deploy/kubernetes/configmap.yaml` and `deploy/kubernetes/ingress.yaml`, then:

```bash
kubectl apply -k deploy/kubernetes
kubectl -n omnigocrm rollout status deployment/omnigocrm
```

The included MariaDB StatefulSet is a single-instance baseline. For high-availability production, use a managed database or database operator with tested backups and failover.

## Security

Production disables EspoCRM admin upgrades and extension uploads. EspoCRM recommends keeping these UI capabilities disabled in production. citeturn0search5

Never commit:

- passwords or API tokens;
- production `.env` files;
- Kubernetes Secrets containing real credentials;
- WhatsApp, billing or push credentials;
- production backups.

Use an external secret manager for larger deployments.

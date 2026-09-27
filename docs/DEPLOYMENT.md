# Production Deployment

OmniGoCRM uses EspoCRM as its only CRM/data backend. The production stack uses PostgreSQL for the database, with Docker Compose, HTTPS reverse proxy, secrets, scheduled processing, backups, Kubernetes manifests, and a release image workflow.

EspoCRM supports PostgreSQL 15 and above and its Docker configuration exposes `Postgresql` as a supported database platform. citeturn0search0turn0search4

## Production baseline

The recommended baseline is Docker Compose on a Linux VPS or dedicated server. PostgreSQL 18.6 is used here because PostgreSQL 18 is the current supported major release and 18.6 is the current minor release listed by PostgreSQL. PostgreSQL majors receive five years of support, and PostgreSQL recommends using the current minor release for a supported major version. citeturn0search1turn0search7

### Files

- `docker-compose.yml` — production stack.
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

Set the real domain, TLS email and site URL. Keep the validated EspoCRM and PostgreSQL versions pinned.

Create secrets:

```bash
mkdir -p secrets
openssl rand -base64 36 > secrets/db_password.txt
openssl rand -base64 36 > secrets/admin_password.txt
chmod 600 secrets/*.txt
```

Validate:

```bash
docker compose -f docker-compose.yml config
```

Start:

```bash
docker compose -f docker-compose.yml up -d --build
docker compose -f docker-compose.yml ps
```

Only ports 80/443 are published. PostgreSQL is isolated on an internal Docker network.

Caddy obtains and renews TLS certificates automatically when DNS points the domain at the server. EspoCRM documents Caddy as a supported Docker reverse-proxy approach. citeturn0search3

## Backups

Run:

```bash
bash scripts/backup-production.sh
```

Store backups outside the production server too. Regularly test restoration.

## Updates

Do not deploy a floating `latest` image. Validate a specific EspoCRM release, build the OmniGoCRM image, run tests and migrations, then deploy the immutable version. EspoCRM documents version pinning as the way to handle incompatible customizations during upgrades. citeturn0search4

## Kubernetes

The Kubernetes baseline includes:

- Namespace
- ConfigMap
- Persistent storage
- PostgreSQL StatefulSet
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
  --from-literal=admin-password='REPLACE_ME'
```

Replace `crm.example.com` in `deploy/kubernetes/configmap.yaml` and `deploy/kubernetes/ingress.yaml`, then:

```bash
kubectl apply -k deploy/kubernetes
kubectl -n omnigocrm rollout status deployment/omnigocrm
```

The included PostgreSQL StatefulSet is a single-instance baseline. For high-availability production, use a managed PostgreSQL service or PostgreSQL operator with tested backups and failover.

## Security

Production disables EspoCRM admin upgrades and extension uploads. EspoCRM recommends keeping these UI capabilities disabled in production. citeturn0search5

Never commit:

- passwords or API tokens;
- production `.env` files;
- Kubernetes Secrets containing real credentials;
- WhatsApp, billing or push credentials;
- production backups.

Use an external secret manager for larger deployments.

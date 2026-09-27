# OmniGoCRM Installation Guide

OmniGoCRM is an EspoCRM-based, self-hosted CRM/SaaS platform. **EspoCRM is the only CRM/data backend.** OmniGoCRM functionality is delivered through the custom modules in this repository.

## Installation methods

| Platform | Recommended method | Purpose |
|---|---|---|
| Linux | Docker Compose | Development, homelab, staging and production |
| Windows | Docker Desktop + WSL 2 | Local development |
| macOS | Docker Desktop | Local development |
| Docker | Docker Compose | Standard deployment |
| Kubernetes | Kubernetes manifests | Cluster deployment |
| VPS / dedicated server | Linux + Docker Compose | Production |

The repository has **one Compose file**: `docker-compose.yml`. It is the production-oriented PostgreSQL stack.

---

## 1. Prerequisites

### Docker installation

You need:

- Git
- Docker Engine or Docker Desktop
- Docker Compose v2
- OpenSSL for generating secrets

Clone the repository:

```bash
git clone https://github.com/Shivanshukesarwani/omnigocrm.git
cd omnigocrm
```

For production, use a reviewed release/commit rather than an active development branch.

---

## 2. Docker Compose

### Production configuration

Copy the environment template:

```bash
cp .env.example .env
```

Edit `.env` and set:

```text
OMNIGOCRM_DOMAIN=crm.example.com
OMNIGOCRM_TLS_EMAIL=admin@example.com
OMNIGOCRM_SITE_URL=https://crm.example.com
```

Keep the PostgreSQL and EspoCRM versions pinned.

### Create secrets

Create the required Docker secret files:

```bash
mkdir -p secrets

openssl rand -base64 36 > secrets/db_password.txt
openssl rand -base64 36 > secrets/admin_password.txt

chmod 600 secrets/*.txt
```

Never commit the `secrets/` files.

### Validate

```bash
docker compose config
```

### Start

```bash
docker compose up -d --build
```

Check the services:

```bash
docker compose ps
```

The production stack contains:

- PostgreSQL
- OmniGoCRM / EspoCRM
- EspoCRM scheduled-job daemon
- Caddy HTTPS reverse proxy

Only ports **80 and 443** are exposed by the Compose stack. PostgreSQL is kept on the internal Docker network.

### Open OmniGoCRM

Point your DNS record to the server:

```text
crm.example.com → SERVER_IP
```

Then open:

```text
https://crm.example.com
```

Caddy obtains and renews the TLS certificate automatically when DNS and the server are correctly configured.

### Stop

```bash
docker compose down
```

Do not use `docker compose down -v` on a production installation unless you intentionally want to delete persistent database/application volumes.

---

## 3. Linux

### Recommended: Docker Compose

Install Docker Engine and Docker Compose v2 for your Linux distribution.

Then:

```bash
git clone https://github.com/Shivanshukesarwani/omnigocrm.git
cd omnigocrm

cp .env.example .env
# Edit .env

mkdir -p secrets
openssl rand -base64 36 > secrets/db_password.txt
openssl rand -base64 36 > secrets/admin_password.txt
chmod 600 secrets/*.txt

docker compose config
docker compose up -d --build
docker compose ps
```

For a VPS or dedicated server, also configure:

- DNS
- Firewall
- HTTPS
- Backups
- Server updates
- Monitoring
- External backup storage

### Native Linux

A native installation without Docker is possible using the upstream EspoCRM requirements, PHP, Composer, PostgreSQL and a supported web server.

For a production OmniGoCRM deployment, Docker Compose is the maintained deployment path in this repository because it keeps the application, PostgreSQL database, scheduled jobs and HTTPS proxy versioned together.

---

## 4. Windows

### Docker Desktop + WSL 2

Install:

1. Docker Desktop for Windows.
2. WSL 2.
3. Ubuntu or another supported Linux distribution.
4. Git.

From WSL:

```bash
git clone https://github.com/Shivanshukesarwani/omnigocrm.git
cd omnigocrm
```

For a real server/domain deployment, follow the Docker Compose production setup above.

For local development, you can still build the OmniGoCRM image:

```bash
docker build -f Dockerfile.espocrm -t omnigocrm:local .
```

The production Compose file is designed around HTTPS and a real domain, so it should not be treated as a simple localhost-only development stack without adapting its domain/Caddy configuration.

Native Windows PHP/IIS is not the primary deployment path.

---

## 5. macOS

### Docker Desktop

Install:

1. Docker Desktop for macOS.
2. Git.

Clone the repository:

```bash
git clone https://github.com/Shivanshukesarwani/omnigocrm.git
cd omnigocrm
```

Build the OmniGoCRM image:

```bash
docker build -f Dockerfile.espocrm -t omnigocrm:local .
```

For a real server/domain deployment, use the Docker Compose production procedure in this guide.

Docker Desktop provides the same Linux-container environment used by the production deployment.

---

## 6. Docker image

The repository contains one application Dockerfile:

```text
Dockerfile.espocrm
```

It extends the official EspoCRM image and adds the OmniGoCRM custom modules.

Build it manually:

```bash
docker build \
  -f Dockerfile.espocrm \
  --build-arg ESPOCRM_IMAGE=espocrm/espocrm:10.0.8 \
  -t omnigocrm:10.0.8 .
```

The Compose deployment builds this image automatically.

Do not use a floating `latest` EspoCRM image for production. Pin and validate the exact EspoCRM version used by the OmniGoCRM release.

---

## 7. Kubernetes

The repository includes a Kubernetes deployment under:

```text
deploy/kubernetes/
```

The Kubernetes deployment contains:

- Namespace
- ConfigMap
- Secret example
- PostgreSQL StatefulSet
- PostgreSQL service
- OmniGoCRM Deployment
- OmniGoCRM service
- Scheduled daemon
- Persistent storage
- TLS Ingress

### Create the namespace

```bash
kubectl apply -f deploy/kubernetes/namespace.yaml
```

### Create the production secret

Do not apply `secret.example.yaml` directly.

Create the real secret using your secret manager or:

```bash
kubectl -n omnigocrm create secret generic omnigocrm-secrets \
  --from-literal=db-password='REPLACE_WITH_LONG_RANDOM_PASSWORD' \
  --from-literal=admin-password='REPLACE_WITH_STRONG_ADMIN_PASSWORD'
```

### Configure the domain

Replace `crm.example.com` in:

```text
deploy/kubernetes/configmap.yaml
deploy/kubernetes/ingress.yaml
```

### Deploy

```bash
kubectl apply -k deploy/kubernetes
```

Check:

```bash
kubectl -n omnigocrm get pods
kubectl -n omnigocrm get services
kubectl -n omnigocrm rollout status deployment/omnigocrm
```

The included PostgreSQL StatefulSet is a single-instance baseline. For high availability, use a managed PostgreSQL service or a PostgreSQL operator with tested backup and failover procedures.

---

## 8. Production VPS / Dedicated Server

Linux + Docker Compose is the recommended server deployment.

### Minimum layout

```text
Internet
   |
DNS
   |
Caddy / HTTPS
   |
OmniGoCRM / EspoCRM
   |
PostgreSQL
   |
Persistent storage
```

### Production checklist

- [ ] Linux server installed
- [ ] Docker Engine installed
- [ ] Docker Compose v2 installed
- [ ] Domain DNS configured
- [ ] `.env` configured
- [ ] Strong Docker secrets generated
- [ ] PostgreSQL persistent volume enabled
- [ ] Application persistent volume enabled
- [ ] HTTPS working
- [ ] Firewall configured
- [ ] Scheduled jobs running
- [ ] Database backups configured
- [ ] Application-data backups configured
- [ ] Backups copied to external storage
- [ ] Restore procedure tested
- [ ] Monitoring/logging configured
- [ ] Production image/version pinned

---

## 9. Backups

The repository includes:

```text
scripts/backup-production.sh
```

Run:

```bash
bash scripts/backup-production.sh
```

The backup contains:

- PostgreSQL database dump
- EspoCRM application data

Copy backups to storage outside the production server and regularly test restoration.

---

## 10. Updating OmniGoCRM

Before updating production:

```bash
git fetch --all --tags
git checkout main
git pull --ff-only
```

Build the new application image:

```bash
docker build \
  -f Dockerfile.espocrm \
  --build-arg ESPOCRM_IMAGE=espocrm/espocrm:VERSION \
  -t omnigocrm:VERSION .
```

Review the EspoCRM release and required database migrations before deploying.

Then:

```bash
docker compose up -d --build
docker compose ps
```

Never upgrade a production database without a current tested backup.

---

## 11. Troubleshooting

Check all services:

```bash
docker compose ps
```

Application logs:

```bash
docker compose logs --tail=200 omnigocrm
```

PostgreSQL logs:

```bash
docker compose logs --tail=200 db
```

Daemon logs:

```bash
docker compose logs --tail=200 daemon
```

Caddy logs:

```bash
docker compose logs --tail=200 caddy
```

Check database connectivity:

```bash
docker compose exec db pg_isready -U omnigocrm -d omnigocrm
```

For production issues, check:

1. DNS
2. HTTPS/Caddy
3. PostgreSQL health
4. EspoCRM application health
5. File permissions
6. Persistent storage
7. Scheduled jobs
8. Database migrations
9. Application logs
10. Container/server logs

---

## 12. Security

Never commit:

- PostgreSQL passwords
- EspoCRM admin passwords
- API tokens
- WhatsApp credentials
- Billing credentials
- Push-notification credentials
- Private signing secrets
- Production `.env`
- Production backups
- Kubernetes Secrets containing real credentials

Use Docker/Kubernetes secrets or an external secret manager.

Production should use HTTPS, strong unique credentials, a firewall, regular updates, restricted database access and tested backups.

## Related documentation

- `docs/DEPLOYMENT.md` — production deployment
- `docs/ESPO_BASE.md` — EspoCRM foundation
- `docs/OMNIGOCRM_ROADMAP.md` — product roadmap
- `docs/WACRM_FEATURE_INTEGRATION.md` — WhatsApp/automation integration
- `docker-compose.yml` — production Docker Compose stack
- `Dockerfile.espocrm` — OmniGoCRM application image
- `deploy/kubernetes/` — Kubernetes deployment
- `scripts/backup-production.sh` — production backup

# OmniGoCRM Installation Guide

OmniGoCRM is an EspoCRM-based, self-hosted CRM/SaaS platform. **EspoCRM is the only CRM/data backend.** OmniGoCRM functionality is delivered through the custom modules in this repository.

## Installation methods

| Platform | Recommended method | Use |
|---|---|---|
| Linux | Docker | Development, homelab, staging, production |
| Windows | Docker Desktop + WSL 2 | Development/testing |
| macOS | Docker Desktop | Development/testing |
| Docker | Docker Compose/container image | Portable deployment |
| Kubernetes | Container image + Kubernetes | Cluster deployments |
| VPS/dedicated server | Linux + Docker | Production |

For the simplest installation, use Docker.

## 1. Prerequisites

You need Git and, depending on the installation method, Docker, Docker Compose, or the PHP/PostgreSQL stack required by the selected EspoCRM release.

Clone the repository:

```bash
git clone https://github.com/Shivanshukesarwani/omnigocrm.git
cd omnigocrm
```

For development work:

```bash
git checkout development/master-sequence
```

For production, deploy a reviewed release/commit rather than the development branch.

---

## 2. Linux

### Docker — recommended

Install Docker Engine and the Docker Compose plugin for your Linux distribution.

Build the image:

```bash
docker build -f Dockerfile.espocrm -t omnigocrm:local .
```

For the repository's production Compose environment:

```bash
docker compose -f docker-compose.yml up -d --build
docker compose -f docker-compose.yml ps
```

Open:

```
http://localhost:8081
```

The Compose file contains deliberately local production secrets. **Do not commit production secrets.**

Stop the stack:

```bash
docker compose -f docker-compose.yml down
```

Remove test volumes:

```bash
docker compose -f docker-compose.yml down -v
```

### Native Linux

A native deployment can use Apache/Nginx, PHP, Composer and PostgreSQL.

Install the PHP version and extensions supported by the exact EspoCRM release, Composer, PostgreSQL, Git, a web server, and cron/systemd for scheduled jobs.

Then clone the repository, configure the database and EspoCRM installation according to that EspoCRM release, expose the EspoCRM public application directory through the web server, run the required migration/upgrade procedure, configure scheduled jobs, enable HTTPS, and configure backups.

Do not expose application secrets or writable/private data directories through the public web root.

---

## 3. Windows

### Docker Desktop + WSL 2 — recommended

Install:

1. Docker Desktop for Windows.
2. WSL 2.
3. Ubuntu or another supported Linux distribution under WSL.
4. Git.

From WSL:

```bash
git clone https://github.com/Shivanshukesarwani/omnigocrm.git
cd omnigocrm
docker build -f Dockerfile.espocrm -t omnigocrm:local .
docker compose -f docker-compose.yml up -d --build
docker compose -f docker-compose.yml ps
```

Open:

```
http://localhost:8081
```

Docker Desktop can also run the Linux containers from PowerShell when Linux-container mode is enabled.

Native Windows PHP/IIS is not the recommended development path; the application and CI environment are Linux/container oriented.

---

## 4. macOS

### Docker Desktop — recommended

Install Docker Desktop for macOS and Git.

Then:

```bash
git clone https://github.com/Shivanshukesarwani/omnigocrm.git
cd omnigocrm
docker build -f Dockerfile.espocrm -t omnigocrm:local .
docker compose -f docker-compose.yml up -d --build
docker compose -f docker-compose.yml ps
```

Open:

```
http://localhost:8081
```

Docker Desktop keeps the development environment close to the Linux/container environment used for deployment.

---

## 5. Docker

The repository's `Dockerfile.espocrm` extends the upstream EspoCRM runtime and copies the OmniGoCRM custom backend/frontend extensions into it.

Build:

```bash
docker build -f Dockerfile.espocrm -t omnigocrm:local .
```

Run the production Compose stack:

```bash
docker compose -f docker-compose.yml up -d --build
```

The repository currently provides this Compose file for **integration testing/local development**, not as the final production Compose configuration.

For production Docker deployment, use `docker-compose.yml` with:

- pinned OmniGoCRM/EspoCRM image version or digest;
- PostgreSQL 18.x storage;
- persistent EspoCRM application data;
- strong credentials supplied through secrets/environment management;
- HTTPS through a reverse proxy;
- scheduled jobs;
- backups;
- resource/restart policies.

Do not use `espocrm/espocrm:latest` for a production release. Pin the EspoCRM version or image digest after validating it in CI.

---

## 6. Kubernetes

Kubernetes should run OmniGoCRM as a container while keeping database and application storage persistent.

### Build and publish

```bash
docker build -f Dockerfile.espocrm -t ghcr.io/YOUR-ORG/omnigocrm:VERSION .
docker push ghcr.io/YOUR-ORG/omnigocrm:VERSION
```

Use an immutable version tag or image digest in production.

### Required Kubernetes components

A production cluster should provide:

1. Namespace
2. Secret(s) for database/application credentials
3. ConfigMap for non-secret configuration
4. PostgreSQL StatefulSet or externally managed database
5. PersistentVolumeClaim for database storage
6. OmniGoCRM Deployment
7. PersistentVolumeClaim for EspoCRM writable data
8. Service
9. Ingress/load balancer with TLS
10. Scheduled-job/worker workload
11. Backup/restore strategy

Typical architecture:

```text
Internet
   |
TLS Ingress / Load Balancer
   |
OmniGoCRM Service
   |
OmniGoCRM Deployment
   |
PostgreSQL Service
   |
Persistent Storage

OmniGoCRM
   |
Persistent Application Storage
```

Kubernetes secrets should not be committed as plaintext manifests. Restrict database network access to the application. Configure health probes according to the deployed EspoCRM release.

A Kubernetes production manifest/chart should be versioned against the exact EspoCRM release and image digest being deployed. The Compose file is not a Kubernetes production configuration.

---

## 7. Production Server / VPS

Linux is recommended for VPS/dedicated servers.

### Recommended layout

```text
Internet
   |
DNS
   |
HTTPS reverse proxy
   |
OmniGoCRM / EspoCRM
   |
PostgreSQL
   |
Persistent storage
```

Typical components:

- Ubuntu/Debian/RHEL-compatible Linux
- Docker + Docker Compose, or Nginx/Apache + PHP + Composer
- PostgreSQL
- HTTPS
- Firewall
- Backups
- Monitoring/logging
- Cron/systemd or a containerized scheduler

### Production checklist

- [ ] Deploy a fixed OmniGoCRM release/commit.
- [ ] Pin the EspoCRM image/version.
- [ ] Create a dedicated production database.
- [ ] Use strong unique credentials.
- [ ] Configure the production site URL.
- [ ] Enable HTTPS.
- [ ] Configure the reverse proxy.
- [ ] Configure scheduled jobs.
- [ ] Configure persistent storage.
- [ ] Back up database and application data.
- [ ] Test database restoration.
- [ ] Restrict database/server ports with a firewall.
- [ ] Configure integration secrets only for enabled services.
- [ ] Run CI before release.
- [ ] Validate migrations before upgrades.

---

## 8. Updating

Source installation:

```bash
git fetch --all --tags
git checkout main
git pull --ff-only
```

Build a new Docker image:

```bash
docker build -f Dockerfile.espocrm -t omnigocrm:VERSION .
```

Test the new release before production. Run the EspoCRM upgrade/migration procedure required by the selected release before/while deploying an application version that requires it.

For Kubernetes, update the Deployment to the new immutable image tag/digest and perform the required EspoCRM migration steps.

Never blindly replace a production application without checking database migrations and custom-module compatibility.

---

## 9. Troubleshooting

Check containers:

```bash
docker compose -f docker-compose.yml ps
```

View application logs:

```bash
docker compose -f docker-compose.yml logs --tail=200 omnigocrm
```

View database logs:

```bash
docker compose -f docker-compose.yml logs --tail=200 db
```

If port 8081 is already used, change the host-side port in a local Compose override, for example:

```text
8082:80
```

Then use `http://localhost:8082`.

For production problems check, in order:

1. Database connectivity
2. PHP/EspoCRM compatibility
3. File permissions
4. Writable data directories
5. Migration/upgrade output
6. Scheduled jobs
7. Reverse proxy
8. HTTPS
9. Application logs
10. Server/container logs

---

## 10. Security

Never use the production secrets from `docker-compose.yml` in production.

Never commit:

- database passwords;
- API tokens;
- WhatsApp access tokens;
- billing credentials;
- private signing secrets;
- push credentials;
- production environment files containing secrets.

Use environment variables, Docker/Kubernetes secrets, or an external secret manager.

Production installations must use HTTPS and keep private application data outside the public web root.

## Related documentation

- `docs/DEPLOYMENT.md` — production deployment and release guidance
- `docs/ESPO_BASE.md` — EspoCRM foundation
- `docs/OMNIGOCRM_ROADMAP.md` — product roadmap
- `docs/WACRM_FEATURE_INTEGRATION.md` — WhatsApp/automation integration
- `docker-compose.yml` — local integration-test stack
- `Dockerfile.espocrm` — OmniGoCRM container build

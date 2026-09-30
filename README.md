# OmniGoCRM

Open-source, self-hostable omnichannel CRM built entirely by OmniGoCRM.

Independent platform: no EspoCRM, no Laravel, and no other CRM backend.

## Technology stack
- **Node.js 22 + Fastify** — API, authentication and business logic
- **PostgreSQL 18 + PL/pgSQL** — primary database, integrity rules, triggers and transactional database functions
- **JavaScript (ES modules)** — API and background worker runtime
- **React + JavaScript (JSX)** — web application
- **Docker / Kubernetes** — deployment

## Included
- Authentication and workspace isolation
- RBAC: owner, admin, manager, agent, viewer
- Leads, contacts, accounts, opportunities and pipelines
- Tasks, notes, tags and audit logs
- Conversations and messages
- Notifications
- Public lead capture API
- React web dashboard
- Background worker foundation
- Docker and Kubernetes deployment
- PostgreSQL database and versioned API

## Development
1. Copy .env.example to .env and set JWT_SECRET.
2. Start PostgreSQL/Redis: docker compose -f infra/docker/docker-compose.dev.yml up -d postgres redis
3. Install: pnpm install
4. Migrate: pnpm db:migrate
5. Start: pnpm dev

API: http://localhost:3000
Web: http://localhost:5173

The first registered account creates a workspace and becomes its owner.

See docs/ARCHITECTURE.md, docs/API.md, docs/SECURITY.md and docs/DEPLOYMENT.md.

## Deployment options

OmniGoCRM supports direct Linux installation, Docker, Kubernetes, cloud/VPS deployment and managed/custom infrastructure. The interactive installer lets the operator choose the target. See docs/DEPLOYMENT.md for the full deployment matrix.

## Automatic Git deployment

You do not need to manually install Node.js, pnpm, PostgreSQL or Redis for the production Docker deployment.

### Linux
```bash
curl -fsSL https://raw.githubusercontent.com/Shivanshukesarwani/omnigocrm/main/scripts/install.sh | sh
```

The installer automatically:
1. Installs Git/curl if missing.
2. Installs Docker Engine on supported Linux distributions if missing.
3. Clones or updates OmniGoCRM.
4. Creates a production `.env`.
5. Generates random PostgreSQL and JWT secrets when OpenSSL is available.
6. Builds all OmniGoCRM containers.
7. Starts PostgreSQL, Redis, API, worker and web services.
8. Runs database migrations automatically.

### Windows PowerShell
Run:
```powershell
irm https://raw.githubusercontent.com/Shivanshukesarwani/omnigocrm/main/scripts/install.ps1 | iex
```

Windows uses `winget` to install Git and Docker Desktop when available.

### macOS
The same Linux/macOS installer detects macOS:
```bash
curl -fsSL https://raw.githubusercontent.com/Shivanshukesarwani/omnigocrm/main/scripts/install.sh | sh
```

macOS uses Homebrew and Docker Desktop. Docker Desktop must be started before the deployment can continue.

After deployment, open **http://localhost**.

## Automatic HTTPS with Let's Encrypt

For public deployments, OmniGoCRM can automatically obtain and renew a free Let's Encrypt certificate.

- Native Linux: Certbot + Nginx, with automatic renewal.
- Docker: Certbot runs with the web container; certificates persist in Docker volumes and renewal is checked automatically.
- Kubernetes: cert-manager + Let's Encrypt ClusterIssuer with automatic renewal.
- HTTP requests are redirected to HTTPS after HTTPS is configured.

A real DNS domain must point to the server. For HTTP-01 validation, port 80 must be reachable from the internet. Localhost-only installations remain HTTP because they do not have a publicly validated domain.

Set `OMNIGOCRM_DOMAIN` and `LETSENCRYPT_EMAIL` for unattended deployment, or let the interactive installer ask for them.

# Deployment

OmniGoCRM includes a one-command production Docker deployment for Linux, Windows and macOS.

## One-click / one-command Docker deployment

### Linux
```bash
git clone https://github.com/Shivanshukesarwani/omnigocrm.git
cd omnigocrm
cp .env.production.example .env
# Edit .env and set POSTGRES_PASSWORD and JWT_SECRET
./scripts/deploy.sh
```

### Windows PowerShell
```powershell
git clone https://github.com/Shivanshukesarwani/omnigocrm.git
cd omnigocrm
Copy-Item .env.production.example .env
# Edit .env and set POSTGRES_PASSWORD and JWT_SECRET
./scripts/deploy.ps1
```

### macOS
```bash
git clone https://github.com/Shivanshukesarwani/omnigocrm.git
cd omnigocrm
cp .env.production.example .env
# Edit .env and set POSTGRES_PASSWORD and JWT_SECRET
./scripts/deploy.command
```

The deployment starts PostgreSQL, Redis, the Fastify API, the background worker and the React/Nginx web application. Database migrations run automatically when the API starts.

Open:

`http://localhost`

For a server, replace `CORS_ORIGIN` and `WEB_PORT` in `.env` as appropriate and put TLS/reverse-proxy protection in front of the application.

## Manual Docker deployment

```bash
docker compose --env-file .env -f infra/docker/docker-compose.prod.yml up -d --build
```

## Kubernetes

```bash
kubectl apply -f infra/kubernetes/namespace.yaml
kubectl apply -f infra/kubernetes/app.yaml
```

Production hardening should include TLS, backups, monitoring, secret management, resource limits and a managed/persistent PostgreSQL strategy.

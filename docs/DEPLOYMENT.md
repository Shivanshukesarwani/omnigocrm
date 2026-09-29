# Deployment

OmniGoCRM supports multiple deployment targets. The application code is shared while each deployment adapter provides the required runtime and infrastructure.

## Deployment choices

| Mode | Requirements | Use case |
|---|---|---|
| Direct / native server | Linux, sudo, apt | VPS or dedicated Linux server |
| Docker server | Docker + Compose | Home server, NAS, VPS, production |
| Kubernetes | kubectl + Helm + cluster | Kubernetes/cloud production |
| Cloud / VPS | Any supported VM | AWS, Azure, GCP, DigitalOcean, Hetzner and similar |
| PaaS / custom | Provider-managed runtime | Managed infrastructure |

## Interactive installer

Linux/macOS:

    curl -fsSL https://raw.githubusercontent.com/Shivanshukesarwani/omnigocrm/main/scripts/install.sh | sh

The installer offers native server, Docker, Kubernetes, cloud/VPS and PaaS/custom choices.

Non-interactive modes:

    curl -fsSL https://raw.githubusercontent.com/Shivanshukesarwani/omnigocrm/main/scripts/install.sh | sh -s -- --mode=docker
    curl -fsSL https://raw.githubusercontent.com/Shivanshukesarwani/omnigocrm/main/scripts/install.sh | sh -s -- --mode=native
    curl -fsSL https://raw.githubusercontent.com/Shivanshukesarwani/omnigocrm/main/scripts/install.sh | sh -s -- --mode=kubernetes

Windows PowerShell:

    irm https://raw.githubusercontent.com/Shivanshukesarwani/omnigocrm/main/scripts/install.ps1 | iex

Set OMNIGOCRM_MODE to docker or kubernetes for non-interactive Windows deployment.

## Direct / native Linux

The native installer currently targets apt-based Linux distributions such as Ubuntu and Debian. It installs Git, curl, Node.js 22, pnpm, PostgreSQL, Redis, Nginx and build tools. It then clones or updates the repository, creates production configuration, installs packages, runs migrations, builds the React web application and creates systemd services for the API and worker.

## Docker

Docker mode automatically installs Docker on supported Linux distributions and uses Docker Compose. Windows/macOS use Docker Desktop. The host does not need Node.js, pnpm, PostgreSQL or Redis.

## Kubernetes

The Helm chart is located at deploy/kubernetes/helm/omnigocrm.

Requirements: Kubernetes cluster, kubectl, Helm 3+, persistent storage and access to the OmniGoCRM container images.

Install:

    curl -fsSL https://raw.githubusercontent.com/Shivanshukesarwani/omnigocrm/main/scripts/install.sh | sh -s -- --mode=kubernetes

Or:

    helm upgrade --install omnigocrm deploy/kubernetes/helm/omnigocrm --namespace omnigocrm --create-namespace

The chart deploys PostgreSQL, Redis, API, worker and web. Generated secrets are preserved across Helm upgrades unless an existing secret is supplied.

## Cloud / VPS

OmniGoCRM can run on a normal VM from AWS, Azure, Google Cloud, DigitalOcean, Hetzner and similar providers. Use native or Docker mode on a VM, or Kubernetes mode on a managed Kubernetes service.

## PaaS / custom infrastructure

If the provider supplies managed PostgreSQL/Redis and a Node.js or container runtime, configure DATABASE_URL, REDIS_URL, JWT_SECRET and CORS_ORIGIN. Build the web application with pnpm and run the API/worker package start commands.

## Production hardening

For internet-facing deployments add HTTPS/TLS, DNS, backups, monitoring, rate limiting, secret management, firewall/network policies, resource limits and persistent storage. Never expose PostgreSQL or Redis directly to the public internet.

Pin release versions/images for controlled production deployments instead of tracking main indefinitely.

Security note: piping a remote script directly to a shell is convenient but requires trusting the current GitHub file. For higher assurance, download, inspect and pin a release before execution.
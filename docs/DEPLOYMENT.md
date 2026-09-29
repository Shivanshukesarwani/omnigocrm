# Deployment

Docker is the common deployment path on Linux, Windows and macOS. Native Node.js development works through pnpm.

Development:
docker compose -f infra/docker/docker-compose.dev.yml up -d --build

Kubernetes:
kubectl apply -f infra/kubernetes/namespace.yaml
kubectl apply -f infra/kubernetes/app.yaml

Before production, replace example secrets and image tags, add persistent PostgreSQL storage, TLS/Ingress, backups, monitoring and a proper secret manager.
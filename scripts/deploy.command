#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
if [ ! -f .env ]; then
  cp .env.production.example .env
  echo "Created .env. Set POSTGRES_PASSWORD and JWT_SECRET, then run this script again."
  exit 1
fi
docker compose --env-file .env -f infra/docker/docker-compose.prod.yml up -d --build
echo "OmniGoCRM is running at http://localhost"

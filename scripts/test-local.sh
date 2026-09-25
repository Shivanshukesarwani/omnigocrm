#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Starting OmniGoCRM test stack"
docker compose -f docker-compose.test.yml up -d --build

echo "==> Waiting for the application"
for i in $(seq 1 60); do
  if curl -fsS http://localhost:8081/ >/dev/null 2>&1; then
    echo "OmniGoCRM is responding at http://localhost:8081"
    exit 0
  fi
  sleep 2
done

echo "OmniGoCRM did not become ready within 120 seconds."
docker compose -f docker-compose.test.yml ps
docker compose -f docker-compose.test.yml logs --tail=100 omnigocrm-test
exit 1

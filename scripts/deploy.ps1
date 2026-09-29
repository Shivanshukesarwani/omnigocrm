$ErrorActionPreference = "Stop"
Set-Location (Join-Path $PSScriptRoot "..")
if (!(Test-Path ".env")) {
  Copy-Item ".env.production.example" ".env"
  Write-Host "Created .env. Set POSTGRES_PASSWORD and JWT_SECRET, then run this script again."
  exit 1
}
docker compose --env-file .env -f infra/docker/docker-compose.prod.yml up -d --build
Write-Host "OmniGoCRM is running at http://localhost"

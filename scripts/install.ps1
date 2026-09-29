$ErrorActionPreference = "Stop"

$RepoUrl = "https://github.com/Shivanshukesarwani/omnigocrm.git"
$InstallDir = if ($env:OMNIGOCRM_DIR) { $env:OMNIGOCRM_DIR } else { Join-Path $HOME "omnigocrm" }

function Log($Message) { Write-Host "[OmniGoCRM] $Message" }

$mode = $env:OMNIGOCRM_MODE
if (-not $mode) {
  Write-Host ""
  Write-Host "OmniGoCRM Deployment"
  Write-Host "  1) Direct server (native Linux only)"
  Write-Host "  2) Docker server"
  Write-Host "  3) Kubernetes"
  Write-Host "  4) Cloud / VPS"
  Write-Host "  5) PaaS / custom infrastructure"
  $choice = Read-Host "Select [1-5]"
  $mode = switch ($choice) {
    "1" { "native" }
    "2" { "docker" }
    "3" { "kubernetes" }
    "4" { "cloud" }
    default { "guide" }
  }
}

if ($mode -eq "native") {
  throw "Windows native server mode is not supported. Use Docker or Kubernetes."
}

if ($mode -eq "cloud" -or $mode -eq "guide") {
  Write-Host "Cloud/PaaS deployment guide: https://github.com/Shivanshukesarwani/omnigocrm/blob/main/docs/DEPLOYMENT.md"
  exit 0
}

if ($mode -eq "kubernetes") {
  if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    if (Get-Command winget -ErrorAction SilentlyContinue) { winget install --id Git.Git -e --source winget --accept-package-agreements --accept-source-agreements }
    else { throw "Git is required." }
  }
  if (-not (Get-Command kubectl -ErrorAction SilentlyContinue)) { throw "kubectl is required for Kubernetes deployment." }
  if (-not (Get-Command helm -ErrorAction SilentlyContinue)) { throw "Helm is required for Kubernetes deployment." }
  if (-not (Test-Path (Join-Path $InstallDir ".git"))) { git clone $RepoUrl $InstallDir } else { git -C $InstallDir pull --ff-only }
  Set-Location $InstallDir
  $namespace = if ($env:OMNIGOCRM_NAMESPACE) { $env:OMNIGOCRM_NAMESPACE } else { "omnigocrm" }
  $release = if ($env:OMNIGOCRM_RELEASE) { $env:OMNIGOCRM_RELEASE } else { "omnigocrm" }
  kubectl create namespace $namespace --dry-run=client -o yaml | kubectl apply -f -
  helm upgrade --install $release deploy/kubernetes/helm/omnigocrm --namespace $namespace --create-namespace
  Write-Host "Kubernetes deployment complete."
  exit 0
}

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
  if (Get-Command winget -ErrorAction SilentlyContinue) {
    Log "Installing Git..."
    winget install --id Git.Git -e --source winget --accept-package-agreements --accept-source-agreements
  } else { throw "Git is required." }
}
if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
  if (Get-Command winget -ErrorAction SilentlyContinue) {
    Log "Installing Docker Desktop..."
    winget install --id Docker.DockerDesktop -e --source winget --accept-package-agreements --accept-source-agreements
    Write-Host "Start Docker Desktop and run this installer again."
    exit 0
  } else { throw "Docker Desktop is required." }
}
docker compose version | Out-Null

if (-not (Test-Path (Join-Path $InstallDir ".git"))) { git clone $RepoUrl $InstallDir } else { git -C $InstallDir pull --ff-only }
Set-Location $InstallDir

if (-not (Test-Path ".env")) {
  Copy-Item ".env.production.example" ".env"
  $dbPassword = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32)).ToLower()
  $jwtSecret = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(48)).ToLower()
  (Get-Content ".env") -replace '^POSTGRES_PASSWORD=.*$', "POSTGRES_PASSWORD=$dbPassword" -replace '^JWT_SECRET=.*$', "JWT_SECRET=$jwtSecret" | Set-Content ".env"
}
docker compose --env-file .env -f infra/docker/docker-compose.prod.yml up -d --build
Write-Host "Docker deployment complete: http://localhost"

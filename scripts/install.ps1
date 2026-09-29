$ErrorActionPreference = "Stop"

$RepoUrl = "https://github.com/Shivanshukesarwani/omnigocrm.git"
$InstallDir = if ($env:OMNIGOCRM_DIR) { $env:OMNIGOCRM_DIR } else { Join-Path $HOME "omnigocrm" }

function Log($Message) { Write-Host "[OmniGoCRM] $Message" }

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
  if (Get-Command winget -ErrorAction SilentlyContinue) {
    Log "Installing Git..."
    winget install --id Git.Git -e --source winget --accept-package-agreements --accept-source-agreements
  } else { throw "Git is required. Install Git or enable winget, then retry." }
}

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
  if (Get-Command winget -ErrorAction SilentlyContinue) {
    Log "Installing Docker Desktop..."
    winget install --id Docker.DockerDesktop -e --source winget --accept-package-agreements --accept-source-agreements
    Log "Start Docker Desktop and wait until Docker is running, then run this installer again."
    exit 0
  } else { throw "Docker Desktop is required. Install it or enable winget, then retry." }
}

docker compose version | Out-Null

if (-not (Test-Path (Join-Path $InstallDir ".git"))) {
  Log "Cloning OmniGoCRM..."
  git clone $RepoUrl $InstallDir
} else {
  Log "Updating existing OmniGoCRM checkout..."
  git -C $InstallDir pull --ff-only
}

Set-Location $InstallDir

if (-not (Test-Path ".env")) {
  Copy-Item ".env.production.example" ".env"
  $dbPassword = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32)).ToLower()
  $jwtSecret = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(48)).ToLower()
  (Get-Content ".env") -replace '^POSTGRES_PASSWORD=.*$', "POSTGRES_PASSWORD=$dbPassword" -replace '^JWT_SECRET=.*$', "JWT_SECRET=$jwtSecret" | Set-Content ".env"
}

Log "Building and starting OmniGoCRM..."
docker compose --env-file .env -f infra/docker/docker-compose.prod.yml up -d --build

Log "Deployment complete."
Write-Host "URL: http://localhost"
Write-Host "Directory: $InstallDir"

#!/usr/bin/env sh
set -eu

REPO_URL="https://github.com/Shivanshukesarwani/omnigocrm.git"
INSTALL_DIR="\${OMNIGOCRM_DIR:-$HOME/omnigocrm}"

log() { printf '\n[OmniGoCRM] %s\n' "$1"; }
need_cmd() { command -v "$1" >/dev/null 2>&1; }

install_linux_deps() {
  if ! need_cmd git || ! need_cmd curl; then
    log "Installing Git and curl..."
    if need_cmd apt-get; then
      sudo apt-get update
      sudo apt-get install -y git curl ca-certificates
    elif need_cmd dnf; then
      sudo dnf install -y git curl ca-certificates
    elif need_cmd yum; then
      sudo yum install -y git curl ca-certificates
    elif need_cmd pacman; then
      sudo pacman -Sy --noconfirm git curl ca-certificates
    else
      echo "Please install Git and curl, then run this installer again." >&2
      exit 1
    fi
  fi

  if ! need_cmd docker; then
    log "Installing Docker Engine..."
    curl -fsSL https://get.docker.com | sudo sh
    sudo systemctl enable --now docker 2>/dev/null || true
    sudo usermod -aG docker "$USER" 2>/dev/null || true
  fi
}

install_macos_deps() {
  if ! need_cmd brew; then
    echo "Homebrew is required on macOS for automatic Docker installation." >&2
    echo "Install Homebrew from https://brew.sh/ and run this installer again." >&2
    exit 1
  fi
  if ! need_cmd git; then brew install git; fi
  if ! need_cmd docker; then
    log "Installing Docker Desktop..."
    brew install --cask docker
    open -a Docker || true
    echo "Docker Desktop was installed. Start Docker Desktop, wait until it is running, then run this installer again."
    exit 0
  fi
}

case "$(uname -s)" in
  Linux) install_linux_deps ;;
  Darwin) install_macos_deps ;;
  *) echo "Use scripts/install.ps1 on Windows." >&2; exit 1 ;;
esac

if ! docker compose version >/dev/null 2>&1; then
  echo "Docker Compose is not available. Install/update Docker Desktop or Docker Engine, then retry." >&2
  exit 1
fi

if [ ! -d "$INSTALL_DIR/.git" ]; then
  log "Cloning OmniGoCRM..."
  git clone "$REPO_URL" "$INSTALL_DIR"
else
  log "Updating existing OmniGoCRM checkout..."
  git -C "$INSTALL_DIR" pull --ff-only
fi

cd "$INSTALL_DIR"

if [ ! -f .env ]; then
  cp .env.production.example .env
  if need_cmd openssl; then
    DB_PASS="$(openssl rand -hex 32)"
    JWT_SECRET="$(openssl rand -hex 48)"
    sed -i.bak "s/^POSTGRES_PASSWORD=.*/POSTGRES_PASSWORD=$DB_PASS/" .env
    sed -i.bak "s/^JWT_SECRET=.*/JWT_SECRET=$JWT_SECRET/" .env
    rm -f .env.bak
  fi
  chmod 600 .env 2>/dev/null || true
fi

log "Building and starting OmniGoCRM..."
docker compose --env-file .env -f infra/docker/docker-compose.prod.yml up -d --build

log "Deployment complete."
echo "URL: http://localhost"
echo "Directory: $INSTALL_DIR"

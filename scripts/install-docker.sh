#!/usr/bin/env sh
set -eu

REPO_URL="https://github.com/Shivanshukesarwani/omnigocrm.git"
INSTALL_DIR="${OMNIGOCRM_DIR:-$HOME/omnigocrm}"

log() { printf '\n[OmniGoCRM] %s\n' "$1"; }
need_cmd() { command -v "$1" >/dev/null 2>&1; }

install_linux_deps() {
  if ! need_cmd git || ! need_cmd curl; then
    if need_cmd apt-get; then sudo apt-get update && sudo apt-get install -y git curl ca-certificates
    elif need_cmd dnf; then sudo dnf install -y git curl ca-certificates
    elif need_cmd yum; then sudo yum install -y git curl ca-certificates
    elif need_cmd pacman; then sudo pacman -Sy --noconfirm git curl ca-certificates
    else echo "Install Git and curl, then retry." >&2; exit 1; fi
  fi
  if ! need_cmd docker; then
    log "Installing Docker Engine..."
    curl -fsSL https://get.docker.com | sudo sh
    sudo systemctl enable --now docker 2>/dev/null || true
    sudo usermod -aG docker "$USER" 2>/dev/null || true
    if ! docker version >/dev/null 2>&1; then
      echo "Docker was installed. Log out/in if group permissions changed, then run again." >&2
      exit 0
    fi
  fi
}

install_macos_deps() {
  if ! need_cmd brew; then
    echo "Homebrew is required on macOS. Install it from https://brew.sh/ and retry." >&2
    exit 1
  fi
  need_cmd git || brew install git
  if ! need_cmd docker; then
    brew install --cask docker
    open -a Docker || true
    echo "Start Docker Desktop and run this installer again."
    exit 0
  fi
}

case "$(uname -s)" in
  Linux) install_linux_deps ;;
  Darwin) install_macos_deps ;;
  *) echo "Unsupported OS. Use install.ps1 on Windows." >&2; exit 1 ;;
esac

docker compose version >/dev/null 2>&1 || { echo "Docker Compose is unavailable." >&2; exit 1; }

if [ ! -d "$INSTALL_DIR/.git" ]; then
  log "Cloning OmniGoCRM..."
  git clone "$REPO_URL" "$INSTALL_DIR"
else
  log "Updating OmniGoCRM..."
  git -C "$INSTALL_DIR" pull --ff-only
fi
cd "$INSTALL_DIR"

if [ ! -f .env ]; then
  cp .env.production.example .env
  if need_cmd openssl; then
    sed -i.bak "s/^POSTGRES_PASSWORD=.*/POSTGRES_PASSWORD=$(openssl rand -hex 32)/" .env
    sed -i.bak "s/^JWT_SECRET=.*/JWT_SECRET=$(openssl rand -hex 48)/" .env
    rm -f .env.bak
  fi
  chmod 600 .env 2>/dev/null || true
fi

log "Building and starting Docker deployment..."
docker compose --env-file .env -f infra/docker/docker-compose.prod.yml up -d --build
log "Docker deployment complete: http://localhost"

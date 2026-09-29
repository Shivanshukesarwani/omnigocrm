#!/usr/bin/env sh
set -eu

INSTALL_DIR="${OMNIGOCRM_DIR:-$HOME/omnigocrm}"
REPO_URL="https://github.com/Shivanshukesarwani/omnigocrm.git"

log(){ printf '\n[OmniGoCRM] %s\n' "$1"; }
need(){ command -v "$1" >/dev/null 2>&1; }
as_root(){ if [ "$(id -u)" -eq 0 ]; then "$@"; else sudo "$@"; fi; }

if [ "$(uname -s)" != "Linux" ]; then
  echo "Direct/native server installation currently supports Linux servers (Ubuntu/Debian)." >&2
  echo "For Windows/macOS use Docker; for Kubernetes use install.sh --mode kubernetes." >&2
  exit 1
fi

if ! need apt-get; then
  echo "Native installation currently requires an apt-based Linux distribution." >&2
  exit 1
fi

log "Installing native server dependencies..."
as_root apt-get update
as_root apt-get install -y git curl ca-certificates nginx postgresql redis-server build-essential

if ! need node; then
  log "Installing Node.js 22..."
  curl -fsSL https://deb.nodesource.com/setup_22.x | as_root bash -
  as_root apt-get install -y nodejs
fi

if ! need pnpm; then
  corepack enable
  corepack prepare pnpm@10.15.0 --activate
fi

if [ ! -d "$INSTALL_DIR/.git" ]; then
  git clone "$REPO_URL" "$INSTALL_DIR"
else
  git -C "$INSTALL_DIR" pull --ff-only
fi
cd "$INSTALL_DIR"

if [ ! -f .env ]; then
  cp .env.example .env
  if need openssl; then
    sed -i "s/^JWT_SECRET=.*/JWT_SECRET=$(openssl rand -hex 48)/" .env
  fi
  chmod 600 .env
fi

DB_NAME="$(sed -n 's/^POSTGRES_DB=//p' .env | head -1)"
DB_USER="$(sed -n 's/^POSTGRES_USER=//p' .env | head -1)"
DB_PASS="$(sed -n 's/^POSTGRES_PASSWORD=//p' .env | head -1)"
DB_NAME="${DB_NAME:-omnigocrm}"
DB_USER="${DB_USER:-omnigocrm}"
DB_PASS="${DB_PASS:-omnigocrm}"

as_root -u postgres psql -tc "SELECT 1 FROM pg_roles WHERE rolname='$DB_USER'" | grep -q 1 || as_root -u postgres psql -c "CREATE USER $DB_USER WITH PASSWORD '$DB_PASS';"
as_root -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname='$DB_NAME'" | grep -q 1 || as_root -u postgres createdb -O "$DB_USER" "$DB_NAME"

pnpm install --no-frozen-lockfile
pnpm db:migrate

mkdir -p "$HOME/.config/systemd/user"
cat > "$HOME/.config/systemd/user/omnigocrm-api.service" <<EOF
[Unit]
Description=OmniGoCRM API
After=postgresql.service redis-server.service
[Service]
WorkingDirectory=$INSTALL_DIR
EnvironmentFile=$INSTALL_DIR/.env
ExecStart=$(command -v pnpm) --filter @omnigocrm/api start
Restart=always
[Install]
WantedBy=default.target
EOF

cat > "$HOME/.config/systemd/user/omnigocrm-worker.service" <<EOF
[Unit]
Description=OmniGoCRM Worker
After=postgresql.service redis-server.service
[Service]
WorkingDirectory=$INSTALL_DIR
EnvironmentFile=$INSTALL_DIR/.env
ExecStart=$(command -v pnpm) --filter @omnigocrm/worker start
Restart=always
[Install]
WantedBy=default.target
EOF

pnpm --filter @omnigocrm/web build

as_root tee /etc/nginx/sites-available/omnigocrm >/dev/null <<EOF
server {
  listen 80 default_server;
  server_name _;
  root $INSTALL_DIR/apps/web/dist;
  location /api/ { proxy_pass http://127.0.0.1:3000; proxy_set_header Host \$host; proxy_set_header X-Real-IP \$remote_addr; }
  location /health { proxy_pass http://127.0.0.1:3000; }
  location / { try_files \$uri \$uri/ /index.html; }
}
EOF
as_root ln -sf /etc/nginx/sites-available/omnigocrm /etc/nginx/sites-enabled/omnigocrm
as_root rm -f /etc/nginx/sites-enabled/default
as_root nginx -t
as_root systemctl enable --now nginx postgresql redis-server

systemctl --user daemon-reload
systemctl --user enable --now omnigocrm-api.service omnigocrm-worker.service

log "Native installation complete: http://localhost"

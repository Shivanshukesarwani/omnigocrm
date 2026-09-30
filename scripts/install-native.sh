#!/usr/bin/env sh
set -eu

INSTALL_DIR="${OMNIGOCRM_DIR:-$HOME/omnigocrm}"
REPO_URL="https://github.com/Shivanshukesarwani/omnigocrm.git"

log(){ printf '\n[OmniGoCRM] %s\n' "$1"; }
need(){ command -v "$1" >/dev/null 2>&1; }
as_root(){ if [ "$(id -u)" -eq 0 ]; then "$@"; else sudo "$@"; fi; }

if [ "$(uname -s)" != "Linux" ]; then
  echo "Direct/native installation currently supports Linux servers (Ubuntu/Debian)." >&2
  echo "Use Docker on Windows/macOS or Kubernetes for cluster deployments." >&2
  exit 1
fi
if ! need apt-get; then
  echo "Native installation currently requires an apt-based Linux distribution." >&2
  exit 1
fi

log "Installing native server dependencies..."
as_root apt-get update
as_root apt-get install -y git curl ca-certificates nginx postgresql redis-server build-essential certbot python3-certbot-nginx

if ! need node; then
  log "Installing Node.js 22..."
  curl -fsSL https://deb.nodesource.com/setup_22.x | as_root bash -
  as_root apt-get install -y nodejs
fi

if ! need pnpm; then
  as_root npm install -g pnpm@10.15.0
fi

if [ ! -d "$INSTALL_DIR/.git" ]; then
  git clone "$REPO_URL" "$INSTALL_DIR"
else
  git -C "$INSTALL_DIR" pull --ff-only
fi
cd "$INSTALL_DIR"

DB_NAME="omnigocrm"
DB_USER="omnigocrm"
DB_PASS="$(openssl rand -hex 32)"
JWT_SECRET="$(openssl rand -hex 48)"
DOMAIN="${OMNIGOCRM_DOMAIN:-}"
LETSENCRYPT_EMAIL="${LETSENCRYPT_EMAIL:-}"

if [ ! -f .env ]; then
  cat > .env <<EOF
NODE_ENV=production
PORT=3000
WEB_URL=http://localhost
DATABASE_URL=postgresql://$DB_USER:$DB_PASS@localhost:5432/$DB_NAME
REDIS_URL=redis://localhost:6379
JWT_SECRET=$JWT_SECRET
CORS_ORIGIN=http://localhost
DOMAIN=$DOMAIN
LETSENCRYPT_EMAIL=$LETSENCRYPT_EMAIL
EOF
  chmod 600 .env
fi

DB_PASS="$(sed -n 's#^DATABASE_URL=postgresql://[^:]*:\([^@]*\)@.*#\1#p' .env | head -1)"
DB_PASS="${DB_PASS:-$(openssl rand -hex 32)}"

as_root -u postgres psql -tc "SELECT 1 FROM pg_roles WHERE rolname='$DB_USER'" | grep -q 1 || as_root -u postgres psql -c "CREATE USER $DB_USER WITH PASSWORD '$DB_PASS';"
as_root -u postgres psql -c "ALTER USER $DB_USER WITH PASSWORD '$DB_PASS';"
as_root -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname='$DB_NAME'" | grep -q 1 || as_root -u postgres createdb -O "$DB_USER" "$DB_NAME"

pnpm install --no-frozen-lockfile
pnpm db:migrate
pnpm --filter @omnigocrm/web build

SERVICE_USER="${SUDO_USER:-$USER}"
PNPM_BIN="$(command -v pnpm)"

as_root tee /etc/systemd/system/omnigocrm-api.service >/dev/null <<EOF
[Unit]
Description=OmniGoCRM API
After=postgresql.service redis-server.service
[Service]
Type=simple
User=$SERVICE_USER
WorkingDirectory=$INSTALL_DIR
EnvironmentFile=$INSTALL_DIR/.env
ExecStart=$PNPM_BIN --filter @omnigocrm/api start
Restart=always
RestartSec=5
[Install]
WantedBy=multi-user.target
EOF

as_root tee /etc/systemd/system/omnigocrm-worker.service >/dev/null <<EOF
[Unit]
Description=OmniGoCRM Worker
After=postgresql.service redis-server.service
[Service]
Type=simple
User=$SERVICE_USER
WorkingDirectory=$INSTALL_DIR
EnvironmentFile=$INSTALL_DIR/.env
ExecStart=$PNPM_BIN --filter @omnigocrm/worker start
Restart=always
RestartSec=5
[Install]
WantedBy=multi-user.target
EOF

as_root tee /etc/nginx/sites-available/omnigocrm >/dev/null <<EOF
server {
  listen 80 default_server;
  server_name _;
  root $INSTALL_DIR/apps/web/dist;
  location /api/ {
    proxy_pass http://127.0.0.1:3000;
    proxy_set_header Host \$host;
    proxy_set_header X-Real-IP \$remote_addr;
  }
  location /health { proxy_pass http://127.0.0.1:3000; }
  location / { try_files \$uri \$uri/ /index.html; }
}
EOF

as_root ln -sf /etc/nginx/sites-available/omnigocrm /etc/nginx/sites-enabled/omnigocrm
as_root rm -f /etc/nginx/sites-enabled/default
as_root nginx -t
as_root systemctl enable --now nginx postgresql redis-server
as_root systemctl daemon-reload
as_root systemctl enable --now omnigocrm-api.service omnigocrm-worker.service

DOMAIN="$(sed -n 's/^DOMAIN=//p' .env | head -1)"
LETSENCRYPT_EMAIL="$(sed -n 's/^LETSENCRYPT_EMAIL=//p' .env | head -1)"
if [ -n "$DOMAIN" ]; then
  [ -n "$LETSENCRYPT_EMAIL" ] || { echo "LETSENCRYPT_EMAIL is required when DOMAIN is set." >&2; exit 1; }
  log "Requesting Let's Encrypt certificate for $DOMAIN..."
  as_root certbot --nginx --non-interactive --agree-tos --no-eff-email --email "$LETSENCRYPT_EMAIL" -d "$DOMAIN" --redirect
  log "HTTPS enabled and automatic renewal configured by Certbot."
  log "Native installation complete: https://$DOMAIN"
else
  log "Native installation complete: http://localhost"
  log "For HTTPS, set OMNIGOCRM_DOMAIN and LETSENCRYPT_EMAIL and rerun the installer."
fi

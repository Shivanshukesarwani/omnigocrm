#!/bin/sh
set -eu

DOMAIN="\${DOMAIN:-}"
EMAIL="\${LETSENCRYPT_EMAIL:-}"
WEBROOT="/var/www/certbot"
CERT_DIR="/etc/letsencrypt/live/\$DOMAIN"

mkdir -p "\$WEBROOT" /etc/letsencrypt

if [ -z "\$DOMAIN" ]; then
  cat > /etc/nginx/conf.d/default.conf <<'NGINX'
server {
  listen 80;
  root /usr/share/nginx/html;
  index index.html;
  location /.well-known/acme-challenge/ { root /var/www/certbot; }
  location /api/ {
    proxy_pass http://api:3000;
    proxy_http_version 1.1;
    proxy_set_header Host \$host;
    proxy_set_header X-Real-IP \$remote_addr;
    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto \$scheme;
  }
  location /health { proxy_pass http://api:3000; }
  location / { try_files \$uri \$uri/ /index.html; }
}
NGINX
  exec nginx -g 'daemon off;'
fi

[ -n "\$EMAIL" ] || { echo "LETSENCRYPT_EMAIL is required when DOMAIN is set." >&2; exit 1; }

if [ ! -f "\$CERT_DIR/fullchain.pem" ] || [ ! -f "\$CERT_DIR/privkey.pem" ]; then
  mkdir -p "\$CERT_DIR"
  openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
    -keyout "\$CERT_DIR/privkey.pem" \
    -out "\$CERT_DIR/fullchain.pem" \
    -subj "/CN=\$DOMAIN" >/dev/null 2>&1 || true
fi

cat > /etc/nginx/conf.d/default.conf <<'NGINX'
server {
  listen 80;
  server_name DOMAIN_PLACEHOLDER;
  location /.well-known/acme-challenge/ { root /var/www/certbot; }
  location / { return 301 https://\$host\$request_uri; }
}
server {
  listen 443 ssl;
  server_name DOMAIN_PLACEHOLDER;
  ssl_certificate /etc/letsencrypt/live/DOMAIN_PLACEHOLDER/fullchain.pem;
  ssl_certificate_key /etc/letsencrypt/live/DOMAIN_PLACEHOLDER/privkey.pem;
  ssl_protocols TLSv1.2 TLSv1.3;
  root /usr/share/nginx/html;
  index index.html;
  location /api/ {
    proxy_pass http://api:3000;
    proxy_http_version 1.1;
    proxy_set_header Host \$host;
    proxy_set_header X-Real-IP \$remote_addr;
    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto https;
  }
  location /health { proxy_pass http://api:3000; }
  location / { try_files \$uri \$uri/ /index.html; }
}
NGINX
sed -i "s/DOMAIN_PLACEHOLDER/\$DOMAIN/g" /etc/nginx/conf.d/default.conf

nginx -t
nginx

if ! certbot certonly --webroot -w "\$WEBROOT" -d "\$DOMAIN" \
  --email "\$EMAIL" --agree-tos --no-eff-email --non-interactive; then
  echo "Let's Encrypt issuance failed. Verify DNS and ports 80/443." >&2
  exit 1
fi

nginx -s reload

while sleep 12h; do
  if certbot renew --webroot -w "\$WEBROOT" --quiet; then
    nginx -s reload
  fi
done

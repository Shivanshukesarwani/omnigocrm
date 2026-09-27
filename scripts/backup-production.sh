#!/usr/bin/env bash
set -Eeuo pipefail

BACKUP_DIR="${BACKUP_DIR:-./backups}"
STAMP="${STAMP:-$(date -u +%Y%m%dT%H%M%SZ)}"
mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

docker compose -f docker-compose.prod.yml exec -T db sh -c \
  'mariadb-dump -u root -p"$(cat /run/secrets/db_root_password)" --all-databases --single-transaction --routines --events' \
  | gzip > "$BACKUP_DIR/omnigocrm-db-$STAMP.sql.gz"

docker compose -f docker-compose.prod.yml exec -T omnigocrm sh -c \
  'tar -C /var/www/html/data -czf - .' \
  > "$BACKUP_DIR/omnigocrm-data-$STAMP.tar.gz"

find "$BACKUP_DIR" -type f -mtime +14 -delete
echo "Backup completed: $STAMP"

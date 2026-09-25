#!/usr/bin/env bash
set -euo pipefail

UPSTREAM_URL="https://github.com/espocrm/espocrm.git"
UPSTREAM_REF="${ESPOCRM_REF:-master}"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

git clone --depth 1 --branch "$UPSTREAM_REF" "$UPSTREAM_URL" "$TMP_DIR/espocrm"

# Overlay upstream EspoCRM without deleting OmniGoCRM-owned files.
# We intentionally avoid rsync --delete so product files are never removed
# merely because they do not exist upstream.
rsync -a   --exclude='.git/'   --exclude='.github/'   --exclude='README.md'   --exclude='docs/'   --exclude='scripts/'   --exclude='Dockerfile*'   --exclude='docker-compose*'   --exclude='custom/Espo/Modules/OmniGoCRM/'   --exclude='client/custom/modules/omni-go-crm/'   --exclude='android/'   --exclude='mobile/'   --exclude='backend/'   "$TMP_DIR/espocrm/" ./

mkdir -p custom/Espo/Modules/OmniGoCRM
mkdir -p client/custom/modules/omni-go-crm

echo "EspoCRM $UPSTREAM_REF synced into OmniGoCRM."

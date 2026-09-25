#!/usr/bin/env bash
set -euo pipefail

UPSTREAM_URL="https://github.com/espocrm/espocrm.git"
UPSTREAM_REF="${ESPOCRM_REF:-master}"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

git clone --depth 1 --branch "$UPSTREAM_REF" "$UPSTREAM_URL" "$TMP_DIR/espocrm"

# Copy EspoCRM core while preserving OmniGoCRM-owned paths.
rsync -a --delete \
  --exclude='.git/' \
  --exclude='custom/Espo/Modules/OmniGoCRM/' \
  --exclude='client/custom/modules/omni-go-crm/' \
  --exclude='docs/' \
  --exclude='android/' \
  --exclude='mobile/' \
  --exclude='backend/' \
  "$TMP_DIR/espocrm/" ./

# Keep our custom directories available even when upstream has an empty custom tree.
mkdir -p custom/Espo/Modules/OmniGoCRM
mkdir -p client/custom/modules/omni-go-crm

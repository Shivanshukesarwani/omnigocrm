#!/usr/bin/env sh
set -eu

REPO_URL="https://github.com/Shivanshukesarwani/omnigocrm.git"
INSTALL_DIR="${OMNIGOCRM_DIR:-$HOME/omnigocrm}"

need(){ command -v "$1" >/dev/null 2>&1; }
log(){ printf '\n[OmniGoCRM] %s\n' "$1"; }

if ! need git; then echo "Git is required." >&2; exit 1; fi
if ! need kubectl; then echo "kubectl is required. Install it and retry." >&2; exit 1; fi
if ! need helm; then echo "Helm is required. Install it and retry." >&2; exit 1; fi

if [ ! -d "$INSTALL_DIR/.git" ]; then git clone "$REPO_URL" "$INSTALL_DIR"; else git -C "$INSTALL_DIR" pull --ff-only; fi
cd "$INSTALL_DIR"

NAMESPACE="${OMNIGOCRM_NAMESPACE:-omnigocrm}"
RELEASE="${OMNIGOCRM_RELEASE:-omnigocrm}"

kubectl get namespace "$NAMESPACE" >/dev/null 2>&1 || kubectl create namespace "$NAMESPACE"

helm upgrade --install "$RELEASE" deploy/kubernetes/helm/omnigocrm   --namespace "$NAMESPACE"   --create-namespace

log "Kubernetes deployment complete."
log "Check status with: kubectl -n $NAMESPACE get pods,svc"

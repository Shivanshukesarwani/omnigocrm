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
DOMAIN="${OMNIGOCRM_DOMAIN:-}"
LETSENCRYPT_EMAIL="${LETSENCRYPT_EMAIL:-}"

kubectl get namespace "$NAMESPACE" >/dev/null 2>&1 || kubectl create namespace "$NAMESPACE"

HELM_ARGS="--namespace $NAMESPACE --create-namespace"
if [ -n "$DOMAIN" ]; then
  [ -n "$LETSENCRYPT_EMAIL" ] || { echo "LETSENCRYPT_EMAIL is required when OMNIGOCRM_DOMAIN is set." >&2; exit 1; }
  log "Installing/updating cert-manager..."
  helm repo add jetstack https://charts.jetstack.io >/dev/null 2>&1 || true
  helm repo update >/dev/null
  helm upgrade --install cert-manager jetstack/cert-manager     --namespace cert-manager --create-namespace     --set crds.enabled=true
  HELM_ARGS="$HELM_ARGS --set ingress.enabled=true --set ingress.tls=true --set ingress.host=$DOMAIN --set certManager.enabled=true --set certManager.email=$LETSENCRYPT_EMAIL"
fi

helm upgrade --install "$RELEASE" deploy/kubernetes/helm/omnigocrm $HELM_ARGS

log "Kubernetes deployment complete."
log "Check status with: kubectl -n $NAMESPACE get pods,svc"

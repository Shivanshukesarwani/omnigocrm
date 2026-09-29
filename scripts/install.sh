#!/usr/bin/env sh
set -eu

REPO_RAW="https://raw.githubusercontent.com/Shivanshukesarwani/omnigocrm/main"
MODE=""
for arg in "$@"; do
  case "$arg" in
    --mode=*) MODE="${arg#*=}" ;;
    --mode) shift; MODE="${1:-}" ;;
    --help|-h)
      echo "OmniGoCRM installer"
      echo "Usage: install.sh [--mode docker|native|kubernetes|cloud|guide]"
      echo "Without --mode, an interactive deployment menu is shown."
      exit 0 ;;
  esac
done

if [ -z "$MODE" ]; then
  printf '\nOmniGoCRM Deployment\n\n'
  printf '  1) Direct server (native Linux)\n'
  printf '  2) Docker server\n'
  printf '  3) Kubernetes\n'
  printf '  4) Cloud / VPS\n'
  printf '  5) PaaS / custom infrastructure\n\n'
  printf 'Select [1-5]: '
  read choice
  case "$choice" in
    1) MODE=native ;;
    2) MODE=docker ;;
    3) MODE=kubernetes ;;
    4) MODE=cloud ;;
    5) MODE=guide ;;
    *) echo "Invalid choice."; exit 1 ;;
  esac
fi

case "$MODE" in
  docker|native|kubernetes)
    exec sh -c "curl -fsSL $REPO_RAW/scripts/install-$MODE.sh | sh"
    ;;
  cloud)
    echo "Cloud/VPS deployments use the same native, Docker or Kubernetes installers."
    echo "See: https://github.com/Shivanshukesarwani/omnigocrm/blob/main/docs/DEPLOYMENT.md"
    echo "Examples: DigitalOcean, Hetzner, AWS EC2, Azure VM, GCP VM."
    ;;
  guide)
    echo "Deployment guide: https://github.com/Shivanshukesarwani/omnigocrm/blob/main/docs/DEPLOYMENT.md"
    ;;
  *)
    echo "Unknown mode: $MODE" >&2
    exit 1
    ;;
esac

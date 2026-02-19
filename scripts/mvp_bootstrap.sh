#!/usr/bin/env bash
set -euo pipefail

if [ ! -f .env ]; then
  cp .env.example .env
fi

docker compose up -d
./scripts/first_setup.sh
./scripts/install_plugins.sh
./scripts/seed_mvp.sh

echo "MVP bootstrap completed. Open http://localhost:8080"

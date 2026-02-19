#!/usr/bin/env bash
set -euo pipefail

WP="docker compose --profile tools run --rm wpcli --allow-root"

install_plugin () {
  local slug="$1"
  local activate="${2:-1}"
  local args=()

  if [ "$activate" = "1" ]; then
    args+=(--activate)
  fi

  if $WP plugin install "$slug" "${args[@]}" >/dev/null 2>&1; then
    if [ "$activate" = "1" ]; then
      echo "installed+activated: $slug"
    else
      echo "installed (inactive): $slug"
    fi
  else
    echo "skipped: $slug (not found or install failed)"
  fi
}

install_plugin wordpress-seo
install_plugin litespeed-cache
install_plugin wordfence 0
install_plugin updraftplus
install_plugin ewww-image-optimizer
install_plugin advanced-custom-fields
install_plugin table-of-contents-plus 0

echo "Plugin install phase completed."

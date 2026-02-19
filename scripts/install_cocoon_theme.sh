#!/usr/bin/env bash
set -euo pipefail

THEMES_DIR="/Users/tnakamura/git/unite-matome/wp-content/themes"
PARENT_DIR="${THEMES_DIR}/cocoon-master"
CHILD_DIR="${THEMES_DIR}/cocoon-child-master"

mkdir -p "${THEMES_DIR}"

install_zip_theme () {
  local url="$1"
  local target_dir="$2"
  local tmp_zip

  if [ -d "${target_dir}" ]; then
    echo "already installed: ${target_dir}"
    return
  fi

  tmp_zip="$(mktemp /tmp/cocoon-theme-XXXXXX.zip)"
  curl -fsSL "${url}" -o "${tmp_zip}"
  unzip -q "${tmp_zip}" -d "${THEMES_DIR}"
  rm -f "${tmp_zip}"
  echo "installed: ${target_dir}"
}

install_zip_theme "https://github.com/xserver-inc/cocoon/archive/refs/heads/master.zip" "${PARENT_DIR}"
install_zip_theme "https://github.com/yhira/cocoon-child/archive/refs/heads/master.zip" "${CHILD_DIR}"

if [ ! -f "${PARENT_DIR}/scss/breakpoints/_max-width-1240.scss" ]; then
  mkdir -p "${PARENT_DIR}/scss/breakpoints"
  cat > "${PARENT_DIR}/scss/breakpoints/_max-width-1240.scss" <<'EOF'
.main {
  width: 100%;
}

.sidebar {
  width: 100%;
}
EOF
  echo "patched: ${PARENT_DIR}/scss/breakpoints/_max-width-1240.scss"
fi

echo "Cocoon theme install completed."

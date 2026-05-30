#!/usr/bin/env bash
# Regenerate the .pot translation template.
#
# Requires WP-CLI: https://wp-cli.org/
#
# Usage:
#   ./bin/make-pot.sh
#
# Run from the repository root.

set -euo pipefail

if ! command -v wp >/dev/null 2>&1; then
  echo "Error: wp-cli not found in PATH. Install from https://wp-cli.org/." >&2
  exit 1
fi

PLUGIN_DIR="zymarg-product-builder"
POT_FILE="${PLUGIN_DIR}/languages/zymarg-product-builder.pot"

mkdir -p "${PLUGIN_DIR}/languages"

wp i18n make-pot "${PLUGIN_DIR}" "${POT_FILE}" \
  --slug=zymarg-product-builder \
  --domain=zymarg-product-builder \
  --package-name="Zymarg Product Builder" \
  --exclude=vendor,node_modules,bin,.github,.git,build

echo "Wrote: ${POT_FILE}"

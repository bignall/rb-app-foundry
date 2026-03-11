#!/usr/bin/env bash
# =============================================================================
# PluginForge — Package Script
# =============================================================================
# Creates a production-ready ZIP file suitable for WordPress plugin upload.
#
# What it does:
#   1. Runs bin/build.sh (Composer + optional JS)
#   2. Assembles only the production files into a clean temp directory
#   3. Creates pluginforge-{version}.zip in the dist/ directory
#
# Usage:
#   bash bin/package.sh             # PHP only
#   bash bin/package.sh --with-js  # Include built React admin
#
# Output:
#   dist/pluginforge-1.0.0.zip
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
PLUGIN_SLUG="pluginforge"
DIST_DIR="$PLUGIN_DIR/dist"

# ── Colours ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()    { echo -e "${CYAN}[PluginForge]${NC} $*"; }
success() { echo -e "${GREEN}[PluginForge]${NC} $*"; }
error()   { echo -e "${RED}[PluginForge] ERROR:${NC} $*" >&2; exit 1; }

# ── Step 1: Build ─────────────────────────────────────────────────────────────
info "Running build step..."
bash "$SCRIPT_DIR/build.sh" "$@"

# ── Read version from plugin file ─────────────────────────────────────────────
MAIN_FILE="$PLUGIN_DIR/${PLUGIN_SLUG}.php"
VERSION=$(grep -m1 "Version:" "$MAIN_FILE" | awk '{print $NF}' | tr -d '\r')
if [[ -z "$VERSION" ]]; then
  error "Could not read version from $MAIN_FILE"
fi

ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"
TEMP_DIR=$(mktemp -d)
STAGE_DIR="$TEMP_DIR/$PLUGIN_SLUG"

info "Packaging v$VERSION → dist/$ZIP_NAME"

# ── Step 2: Assemble production files ─────────────────────────────────────────
mkdir -p "$STAGE_DIR"

# Copy everything, then strip development artifacts below.
rsync -a \
  --exclude=".git" \
  --exclude=".gitignore" \
  --exclude=".gitattributes" \
  --exclude=".editorconfig" \
  --exclude=".DS_Store" \
  --exclude=".env" \
  --exclude=".env.*" \
  --exclude="*.log" \
  --exclude="bin/" \
  --exclude="tests/" \
  --exclude="coverage/" \
  --exclude="dist/" \
  --exclude="node_modules/" \
  --exclude="admin/node_modules/" \
  --exclude="admin/src/" \
  --exclude="admin/package.json" \
  --exclude="admin/package-lock.json" \
  --exclude=".phpunit.result.cache" \
  --exclude="phpunit.xml" \
  --exclude="phpcs.xml" \
  --exclude="phpstan.neon" \
  "$PLUGIN_DIR/" "$STAGE_DIR/"

# Sanity check: vendor/autoload.php must exist.
if [[ ! -f "$STAGE_DIR/vendor/autoload.php" ]]; then
  rm -rf "$TEMP_DIR"
  error "vendor/autoload.php missing — did Composer install succeed?"
fi

# ── Step 3: Create ZIP ────────────────────────────────────────────────────────
mkdir -p "$DIST_DIR"
cd "$TEMP_DIR"
zip -rq "$DIST_DIR/$ZIP_NAME" "$PLUGIN_SLUG/"

# Cleanup.
rm -rf "$TEMP_DIR"

success "Package created: dist/$ZIP_NAME"
info "Upload this file via WordPress Admin → Plugins → Add New → Upload Plugin."

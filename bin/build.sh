#!/usr/bin/env bash
# =============================================================================
# PluginForge — Build Script
# =============================================================================
# Compiles the plugin for deployment:
#   1. Locates PHP 8.0+ and Composer on the host machine
#   2. Runs `composer install --no-dev` to generate the PSR-4 autoloader
#   3. (Optional) Runs the React admin build via npm if Node 18+ is present
#
# Usage:
#   bash bin/build.sh              # PHP + Composer only (safe default)
#   bash bin/build.sh --with-js    # Also build the React admin panel
# =============================================================================

set -euo pipefail

# Ensure Homebrew binaries (php, composer, node) are in PATH.
export PATH="/opt/homebrew/bin:/usr/local/bin:$PATH"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
BUILD_JS=false

# Parse flags.
for arg in "$@"; do
  case $arg in
    --with-js) BUILD_JS=true ;;
  esac
done

# ── Colours ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()    { echo -e "${CYAN}[PluginForge]${NC} $*"; }
success() { echo -e "${GREEN}[PluginForge]${NC} $*"; }
warn()    { echo -e "${YELLOW}[PluginForge]${NC} $*"; }
error()   { echo -e "${RED}[PluginForge] ERROR:${NC} $*" >&2; exit 1; }

info "Starting build in: $PLUGIN_DIR"

# ── Locate PHP 8.0+ ──────────────────────────────────────────────────────────
PHP_BIN=""
for candidate in \
    "/opt/homebrew/bin/php" \
    "/usr/local/opt/php/bin/php" \
    "/usr/local/bin/php" \
    "$(which php 2>/dev/null || true)"; do
  if [[ -x "$candidate" ]]; then
    ver=$("$candidate" -r "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;")
    major="${ver%%.*}"
    if [[ "$major" -ge 8 ]]; then
      PHP_BIN="$candidate"
      break
    fi
  fi
done

if [[ -z "$PHP_BIN" ]]; then
  error "PHP 8.0+ not found. Install it with: brew install php"
fi

PHP_VERSION=$("$PHP_BIN" -r "echo PHP_VERSION;")
success "Using PHP $PHP_VERSION at $PHP_BIN"

# ── Locate Composer ───────────────────────────────────────────────────────────
COMPOSER_BIN=""
for candidate in \
    "/opt/homebrew/bin/composer" \
    "/usr/local/bin/composer" \
    "$HOME/.composer/vendor/bin/composer" \
    "$(which composer 2>/dev/null || true)"; do
  if [[ -x "$candidate" ]]; then
    COMPOSER_BIN="$candidate"
    break
  fi
done

# Fall back to a local composer.phar in the plugin directory.
if [[ -z "$COMPOSER_BIN" ]] && [[ -f "$PLUGIN_DIR/composer.phar" ]]; then
  COMPOSER_BIN="$PHP_BIN $PLUGIN_DIR/composer.phar"
fi

if [[ -z "$COMPOSER_BIN" ]]; then
  error "Composer not found. Install it with: brew install composer"
fi

COMPOSER_VERSION=$($COMPOSER_BIN --version --no-ansi 2>&1 | head -1)
success "Using $COMPOSER_VERSION"

# ── Run Composer ──────────────────────────────────────────────────────────────
info "Installing PHP dependencies (no-dev, optimized autoloader)..."
cd "$PLUGIN_DIR"
$COMPOSER_BIN install \
  --no-dev \
  --optimize-autoloader \
  --no-interaction \
  --prefer-dist

success "Composer install complete."

# ── Build React Admin (optional) ──────────────────────────────────────────────
if [[ "$BUILD_JS" == true ]]; then
  ADMIN_DIR="$PLUGIN_DIR/admin"

  if [[ ! -d "$ADMIN_DIR" ]]; then
    warn "--with-js passed but admin/ directory not found. Skipping JS build."
  else
    # Locate Node 18+.
    NODE_BIN=""
    NVM_LATEST="$HOME/.nvm/versions/node/$(ls "$HOME/.nvm/versions/node/" 2>/dev/null | sort -V | tail -1)/bin/node"
    for candidate in \
        "$(which node 2>/dev/null || true)" \
        "$NVM_LATEST" \
        "/opt/homebrew/bin/node" \
        "/usr/local/bin/node"; do
      if [[ -x "$candidate" ]]; then
        node_major=$("$candidate" -e "console.log(process.versions.node.split('.')[0])")
        if [[ "$node_major" -ge 18 ]]; then
          NODE_BIN="$candidate"
          break
        fi
      fi
    done

    if [[ -z "$NODE_BIN" ]]; then
      warn "Node 18+ not found. Skipping JS build. Install with: nvm install 20"
    else
      NODE_VERSION=$("$NODE_BIN" --version)
      success "Using Node $NODE_VERSION"

      # Add the found node's bin directory to PATH so npm's shebang resolves correctly.
      NODE_BIN_DIR="$(dirname "$NODE_BIN")"
      export PATH="$NODE_BIN_DIR:$PATH"

      info "Installing npm dependencies..."
      cd "$ADMIN_DIR"
      npm install --prefer-offline 2>&1

      info "Building React admin panel..."
      npm run build 2>&1

      success "React admin build complete."
      cd "$PLUGIN_DIR"
    fi
  fi
else
  warn "Skipping React admin build. Run with --with-js to include it."
fi

success "PluginForge build finished."

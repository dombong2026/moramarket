#!/usr/bin/env bash
# Moramarket host and source audit: all checks are read-only.
set -Eeuo pipefail
export LC_ALL=C

if (( $# > 1 )); then echo 'Use: bash support/aapanel-readonly-audit.sh [--help|--ci]' >&2; exit 2; fi
if (( $# == 1 )) && [[ "$1" == '--help' ]]; then
    echo 'Read-only Moramarket audit: no installs, migrations, service restarts, database connections or file writes.'
    echo 'Usage: bash support/aapanel-readonly-audit.sh [--ci]'
    exit 0
fi
if (( $# == 1 )) && [[ "$1" != '--ci' ]]; then echo 'Invalid option' >&2; exit 2; fi

ROOT="$(cd "$(dirname "$0")/.." && pwd -P)"
PASSES=0; WARNINGS=0; BLOCKERS=0
pass() { PASSES=$((PASSES+1)); printf '[PASS] %s\n' "$*"; }
warn() { WARNINGS=$((WARNINGS+1)); printf '[WARN] %s\n' "$*"; }
block() { BLOCKERS=$((BLOCKERS+1)); printf '[BLOCK] %s\n' "$*"; }
info() { printf '[INFO] %s\n' "$*"; }
section() { printf '\n========== %s ==========\n' "$*"; }

echo 'MORAMARKET_AUDIT_MODE=READ_ONLY'
echo 'DATABASE_CONNECTIONS=DISABLED'
echo 'PRODUCTION_WRITES=DISABLED'
info "Inspecting source: $ROOT"

section 'SOURCE AND GITHUB IDENTITY'
if command -v git >/dev/null 2>&1 && [[ -d "$ROOT/.git" ]]; then
    info "Source SHA: $(git -C "$ROOT" rev-parse HEAD 2>/dev/null || true)"
    BRANCH="$(git -C "$ROOT" symbolic-ref --quiet --short HEAD 2>/dev/null || true)"
    info "Source branch: $BRANCH"
    if [[ "$BRANCH" == main ]]; then pass 'Source on main'; else warn 'Source not on main'; fi
    ORIGIN="$(git -C "$ROOT" remote get-url origin 2>/dev/null || true)"
    case "$ORIGIN" in
        https://github.com/dombong2026/moramarket|https://github.com/dombong2026/moramarket.git|git@github.com:dombong2026/moramarket|git@github.com:dombong2026/moramarket.git)
            pass 'Expected Moramarket GitHub origin' ;;
        *) block 'Unrecognized Git remote (URL intentionally not printed)' ;;
    esac
else
    block 'Git metadata unavailable: source identity not verified'
fi

section 'LARAVEL APPLICATION STRUCTURE'
for path in artisan composer.json package.json vite.config.js .env.example \
    app/Http/Controllers/Auth/SessionController.php app/Http/Middleware/SecurityHeaders.php \
    app/Support/WorkspaceNavigation.php bootstrap/app.php \
    config/app.php config/database.php database/migrations \
    resources/views resources/css/app.css resources/js/app.js \
    routes/web.php public/index.php public/installer/index.php \
    storage/app storage/framework/cache storage/framework/sessions \
    storage/framework/views storage/logs bootstrap/cache \
    tests/Feature .github/workflows/ci.yml; do
    if [[ -e "$ROOT/$path" ]]; then pass "Structure: $path"; else block "Missing: $path"; fi
done

section 'PHP CLI AND AAPANEL PHP BINARIES'
SELECTED=''; SELECTED_VERSION=''
if command -v php >/dev/null 2>&1; then
    CLI="$(command -v php)"
    CLI_VERSION="$("$CLI" -r 'echo PHP_VERSION;' 2>/dev/null || true)"
    info "Default CLI PHP: $CLI (version $CLI_VERSION)"
    if "$CLI" -r 'exit(version_compare(PHP_VERSION, "8.3", ">=") ? 0 : 1);' >/dev/null 2>&1; then
        SELECTED="$CLI"; SELECTED_VERSION="$CLI_VERSION"
    fi
else
    warn 'PHP missing from PATH'
fi
for candidate in /www/server/php/*/bin/php; do
    [[ -x "$candidate" && ! -L "$candidate" ]] || continue
    version="$("$candidate" -r 'echo PHP_VERSION;' 2>/dev/null || true)"
    info "aaPanel PHP binary: $candidate ($version)"
    [[ -n "$version" ]] || continue
    if "$candidate" -r 'exit(version_compare(PHP_VERSION, "8.3", ">=") ? 0 : 1);' >/dev/null 2>&1; then
        if [[ -z "$SELECTED_VERSION" ]] || [[ "$(printf '%s\n%s\n' "$SELECTED_VERSION" "$version" | sort -V | tail -n 1)" == "$version" ]]; then
            SELECTED="$candidate"; SELECTED_VERSION="$version"
        fi
    fi
done
if [[ -n "$SELECTED" ]]; then
    pass "PHP 8.3+ available: $SELECTED_VERSION at $SELECTED"
    for ext in ctype curl dom fileinfo filter hash mbstring openssl pcre pdo pdo_mysql session tokenizer xml; do
        if "$SELECTED" -r 'exit(extension_loaded($argv[1]) ? 0 : 1);' "$ext" >/dev/null 2>&1; then
            pass "Required PHP extension: $ext"
        else
            block "Required PHP extension missing: $ext"
        fi
    done
    for ext in gd intl zip sodium exif; do
        if "$SELECTED" -r 'exit(extension_loaded($argv[1]) ? 0 : 1);' "$ext" >/dev/null 2>&1; then
            pass "Recommended PHP extension: $ext"
        else
            warn "Recommended PHP extension missing: $ext"
        fi
    done
else
    block 'No PHP >=8.3 CLI binary found'
fi
info 'Live PHP-FPM version/extensions and Nginx FastCGI mapping: UNVERIFIED (CLI audit only); PHP 8.4 is the existing CI baseline'

section 'COMPOSER AND NODE TOOLCHAINS'
if command -v composer >/dev/null 2>&1; then
    composer_version="$(composer --version --no-ansi 2>/dev/null | head -n 1 || true)"
    # Composer 2.x prints both 'Composer 2.2.6' and 'Composer version 2.9.2' in supported releases.
    if [[ "$composer_version" =~ ^Composer[[:space:]](version[[:space:]])?2\.[0-9]+\.[0-9]+([[:space:]]|$) ]]; then
        pass "$composer_version"
    else
        warn "Composer 2 not confirmed: $composer_version"
    fi
else
    warn 'Composer absent (not necessary for a prebuilt customer package)'
fi
if command -v node >/dev/null 2>&1; then
    node_raw="$(node --version 2>/dev/null || true)"
    major="$(printf '%s' "$node_raw" | sed 's/^v//' | cut -d. -f1)"
    minor="$(printf '%s' "$node_raw" | sed 's/^v//' | cut -d. -f2)"
    if [[ "$major" =~ ^[0-9]+$ && "$minor" =~ ^[0-9]+$ ]]; then
        if (( (major == 20 && minor >= 19) || (major == 22 && minor >= 12) || (major >= 24 && major % 2 == 0) )); then
            pass "Vite 8-compatible Node.js: $node_raw"
        else
            warn "Node.js $node_raw outside validated Vite 8 even-major baseline"
        fi
    else
        warn 'Node.js version could not be parsed'
    fi
else
    warn 'Node.js not found in current PATH (precompiled release assets required)'
fi
if command -v npm >/dev/null 2>&1; then
    info "npm version: $(npm --version 2>/dev/null || true)"
else
    warn 'npm not found in current PATH'
fi

section 'MYSQL/MARIADB BINARIES; NO SQL LOGIN'
db_binary=''
for candidate in /www/server/mysql/bin/mysqld /usr/sbin/mysqld /usr/bin/mysqld /usr/sbin/mariadbd /usr/bin/mariadbd; do
    if [[ -x "$candidate" ]]; then db_binary="$candidate"; break; fi
done
if [[ -n "$db_binary" ]]; then
    pass "SQL daemon binary present: $db_binary"
    info "SQL binary version: $("$db_binary" --version 2>/dev/null | head -n 1 || true)"
else
    warn 'SQL daemon binary not found at standard locations'
fi
if command -v mysql >/dev/null 2>&1; then
    info "MySQL client binary: $(mysql --version 2>/dev/null | head -n 1 || true)"
fi
warn 'Running database version, credentials, connectivity, privileges and schema: UNVERIFIED (no connection attempted)'

section 'FILE PERMISSIONS (READ METADATA ONLY)'
for path in . public public/index.php public/installer/index.php storage storage/app \
    storage/framework storage/framework/cache storage/framework/sessions storage/framework/views \
    storage/logs bootstrap/cache; do
    if [[ -e "$ROOT/$path" ]] && command -v stat >/dev/null 2>&1; then
        mode="$(stat -c '%a' "$ROOT/$path" 2>/dev/null || true)"
        owner="$(stat -c '%U:%G' "$ROOT/$path" 2>/dev/null || true)"
        info "$path: mode=$mode owner=$owner"
        if [[ "$mode" =~ ^[0-7]+$ ]] && (( (8#$mode & 2) != 0 )); then
            warn "World writable path: $path"
        fi
    fi
done
if [[ "$ROOT" == /root/* ]]; then
    pass 'Development source isolated outside /www/wwwroot'
else
    warn 'Source not under /root; verify it is not the live webroot'
fi
info 'Write permission for a future PHP-FPM release user: UNVERIFIED (no write tests)'

section 'AA PANEL WEB AND DISK PREREQUISITES'
if command -v df >/dev/null 2>&1; then
    info "Filesystem free space: $(df -Pk "$ROOT" 2>/dev/null | awk 'NR == 2 {print $4 " KiB"}')"
fi
if command -v nginx >/dev/null 2>&1; then
    info "Nginx: $(nginx -v 2>&1 || true)"
elif [[ -x /www/server/nginx/sbin/nginx ]]; then
    info "aaPanel Nginx: $(/www/server/nginx/sbin/nginx -v 2>&1 || true)"
else
    warn 'Nginx binary not found in common locations'
fi
if [[ -r /www/server/panel/vhost/nginx/moramarket.tonsamith.com.conf ]]; then
    pass 'Moramarket Nginx virtual host configuration exists'
else
    warn 'Moramarket Nginx virtual host not found at the standard aaPanel path'
fi
info 'Live document root, HTTPS, and PHP-FPM site binding: NOT READ OR MODIFIED'

section 'DEPLOYMENT AND INDEPENDENT CUSTOMER RELEASE GATES'
if [[ -f "$ROOT/composer.lock" ]]; then pass 'Composer lockfile present'; else block 'composer.lock missing: dependency versions unpinned'; fi
if [[ -f "$ROOT/package-lock.json" ]]; then pass 'npm lockfile present'; else block 'package-lock.json missing: frontend versions unpinned'; fi
if [[ -f "$ROOT/vendor/autoload.php" ]]; then pass 'Preinstalled Composer dependencies present'; else warn 'vendor/autoload.php absent: expected for source-only clone, required for customer ZIP'; fi
if [[ -f "$ROOT/public/build/manifest.json" ]]; then pass 'Compiled frontend manifest present'; else warn 'public/build/manifest.json absent: expected for source-only clone, required for buyer ZIP'; fi
if [[ -f "$ROOT/.env" ]]; then warn '.env present in development checkout; inspect privately'; else pass 'No .env in source checkout'; fi
if [[ -f "$ROOT/storage/app/.moramarket-installed" ]]; then warn 'Installation marker found in source checkout'; else pass 'No installer completion marker in source checkout'; fi
info 'Independent cPanel/aaPanel install, SMTP, digital entitlements, payments, payouts, policy review and real-browser QA remain open'

section 'READ-ONLY AUDIT SUMMARY'
printf 'PASS_COUNT=%d\nWARN_COUNT=%d\nBLOCK_COUNT=%d\n' "$PASSES" "$WARNINGS" "$BLOCKERS"
echo 'RELEASE_READY=NO'
echo 'AUDIT_STATUS=READ_ONLY_COMPLETE'
echo 'PRODUCTION_WEBSITE=NOT_MODIFIED'
echo 'DATABASE=NOT_CONNECTED'
echo 'SERVER_CONFIG=NOT_MODIFIED'

#!/usr/bin/env bash
# CI-only HTTP security checks of extracted, freshly installed QA ZIP.
set -Eeuo pipefail
die() { printf 'INSTALLER_SECURITY_QA=BLOCKED: %s\n' "$*" >&2; exit 1; }
[[ "$(printenv GITHUB_ACTIONS || :)" == true && "$(printenv CI || :)" == true ]] ||
    die 'Isolated GitHub Actions runner required.'
[[ -n "$(printenv RUNNER_TEMP || :)" && "$PWD" == "$RUNNER_TEMP/"* ]] ||
    die 'Run only in an extracted temporary QA package.'
[[ -f .env && -f storage/app/.moramarket-installed ]] ||
    die 'Run after the package installer succeeds.'
[[ ! -e storage/app/installer-access.key ]] ||
    die 'Installer access code was not removed.'
[[ ! -e public/storage && ! -e public/.env && ! -e public/vendor ]] ||
    die 'Private files or symlinks exposed by public/ directory.'

BASE='http://127.0.0.1:8091'
OUT="$(mktemp "$RUNNER_TEMP/moramarket-private-http.XXXXXXXX")"
SERVER_LOG="$(mktemp "$RUNNER_TEMP/moramarket-private-log.XXXXXXXX")"
SERVER_PID=''
cleanup() {
    if [[ -n "$SERVER_PID" ]]; then
      kill "$SERVER_PID" 2>/dev/null || true
      wait "$SERVER_PID" 2>/dev/null || true
    fi
    rm -f -- "$OUT" "$SERVER_LOG"
}
trap cleanup EXIT
php -S 127.0.0.1:8091 -t public > "$SERVER_LOG" 2>&1 &
SERVER_PID="$!"
for i in 1 2 3 4 5 6 7 8 9 10; do
    if curl --silent --output /dev/null "$BASE/login"; then break; fi
    sleep 1
done
[[ "$(curl -sS -o "$OUT" -w '%{http_code}' "$BASE/login")" == 200 ]] ||
    die 'Installed QA application login route is unavailable.'
for path in '/.env' '/composer.lock' '/vendor/autoload.php' '/storage/app/installer-access.key' '/storage/logs/laravel.log' '/bootstrap/cache/config.php' '/.git/config' '/docs/INSTALLATION.txt'; do
    status="$(curl -sS --path-as-is -o "$OUT" -w '%{http_code}' "$BASE$path")"
    case "$status" in 403|404) ;; *) die "Private resource returned HTTP $status: $path";; esac
    if grep -Eq 'DB_PASSWORD=|APP_KEY=|CI_root_password_891' "$OUT"; then
        die "Private response contained environment markers: $path"
    fi
done
locked="$(curl -sS -o "$OUT" -w '%{http_code}' "$BASE/installer/?step=4")"
[[ "$locked" == 303 || "$locked" == 302 ]] ||
    die "Installed application did not lock setup: HTTP $locked"
echo 'INSTALLER_ACCESS_CODE_REMOVED=PASS'
echo 'PRIVATE_HTTP_PATHS=DENIED'
echo 'POST_INSTALL_LOCK=PASS'
echo 'INSTALLER_SECURITY_QA=PASS'
echo 'PRODUCTION=NOT_ACCESSED'

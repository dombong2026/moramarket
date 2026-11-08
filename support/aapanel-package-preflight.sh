#!/usr/bin/env bash
# Offline, read-only inspection of a locally downloaded GitHub QA archive.
# Does NOT install or extract packages or touch a website.
set -Eeuo pipefail
die() { printf 'ISOLATED_PACKAGE_PREFLIGHT=BLOCKED: %s\n' "$*" >&2; exit 1; }
[[ $# -eq 1 ]] || die 'Usage: bash support/aapanel-package-preflight.sh /root/qa-archive.zip'
command -v unzip >/dev/null || die 'unzip command missing.'
command -v sha256sum >/dev/null || die 'sha256sum command missing.'
[[ -f "$1" && ! -L "$1" ]] || die 'Expected a regular, non-symlinked QA archive.'
ZIP="$(realpath "$1")"
[[ "$ZIP" == /root/* ]] || die 'QA archive must be outside the public web directory under /root.'
[[ "$ZIP" == *.zip ]] || die 'Only ZIP archives supported.'
LIST="$(unzip -Z -1 "$ZIP")" || die 'ZIP cannot be enumerated.'
[[ "$LIST" == *'moramarket/public/index.php'* ]] || die 'Missing Laravel public entrypoint.'
[[ "$LIST" == *'moramarket/public/installer/index.php'* ]] || die 'Missing protected installer.'
[[ "$LIST" == *'moramarket/public/build/manifest.json'* ]] || die 'Compiled Vite manifest missing.'
[[ "$LIST" == *'moramarket/vendor/autoload.php'* ]] || die 'Production dependencies missing.'
[[ "$LIST" == *'moramarket/composer.lock'* ]] || die 'Locked Composer dependencies missing.'
if printf '%s\n' "$LIST" | grep -Eq '(^/|(^|/)\.\.(/|$)|^moramarket/(\.env($|[./])|\.git/|\.github/|node_modules/|tests/|support/)|^moramarket/storage/app/(installer-access.key|\.moramarket-installed)$'; then
    die 'Unsafe or confidential archive entry detected.'
fi
sha256sum "$ZIP"
echo 'ISOLATED_PACKAGE_PREFLIGHT=PASS'
echo 'ARCHIVE_EXTRACTED=NO'
echo 'MYSQL_CONNECTION=NONE'
echo 'PRODUCTION_SITE=UNTOUCHED'
echo 'RELEASE_READY=NO'

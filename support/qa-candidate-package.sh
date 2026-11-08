#!/usr/bin/env bash
# CI-only independent extracted QA of actual packaged ZIP + temporary MySQL 8.
set -Eeuo pipefail
die() { printf 'PACKAGE_QA=BLOCKED: %s\n' "$*" >&2; exit 1; }
[[ "$(printenv GITHUB_ACTIONS || :)" == true && "$(printenv CI || :)" == true ]] ||
  die 'Packaging acceptance can only run in isolated GitHub Actions.'
[[ $# -eq 1 && -f "$1" ]] || die 'Usage: bash support/qa-candidate-package.sh <built-zip>'
ZIP="$(realpath "$1")"
command -v zipinfo >/dev/null || die 'zipinfo command missing.'
command -v unzip >/dev/null || die 'unzip command missing.'

if zipinfo -1 "$ZIP" | grep -Eq '(^/|(^|/)\.\.(/|$))'; then
  die 'Unsafe absolute or parent-traversal ZIP entry.'
fi
if zipinfo -1 "$ZIP" | grep -Eq '^moramarket/(\.env($|[./])|\.git/|\.github/|tests/|support/|node_modules/)'; then
  die 'ZIP contains developer files or environment secrets.'
fi
if zipinfo -1 "$ZIP" | grep -Eq '^moramarket/(storage/app/installer-access.key|storage/app/\.moramarket-installed|bootstrap/cache/config.php)$'; then
  die 'ZIP contains a previously initialized installer or configuration cache.'
fi

QA_ROOT="$(mktemp -d "$RUNNER_TEMP/moramarket-install-qa.XXXXXXXX")"
trap 'rm -rf -- "$QA_ROOT"' EXIT
unzip -q "$ZIP" -d "$QA_ROOT"
APP="$QA_ROOT/moramarket"
[[ -d "$APP" && ! -L "$APP" ]] || die 'Expected single moramarket/ package root.'
for path in artisan composer.lock vendor/autoload.php public/build/manifest.json \
  public/index.php public/installer/index.php bootstrap/app.php \
  storage/app storage/framework/sessions storage/framework/views bootstrap/cache \
  documentation/INSTALLATION.txt documentation/PROPRIETARY_NOTICE.txt \
  BUILD_INFORMATION.txt PACKAGE_CHECKSUMS.txt; do
  [[ -e "$APP/$path" ]] || die "Package missing $path"
done
[[ ! -f "$APP/.env" && ! -e "$APP/.git" && ! -e "$APP/node_modules" ]] ||
  die 'Developer or confidential files leaked into candidate.'
(
  cd "$APP"
  sha256sum -c PACKAGE_CHECKSUMS.txt
  php -l public/index.php
  php -l public/installer/index.php
  php -r 'require "vendor/autoload.php"; echo "Packaged Composer autoloader works\n";'
  php -r '$m=json_decode(file_get_contents("public/build/manifest.json"),true,512,JSON_THROW_ON_ERROR); if(!is_array($m)||!count($m)){exit(1);} echo "Compiled manifest is valid\n";'
  bash "$GITHUB_WORKSPACE/support/qa-installer.sh"
)
echo 'PACKAGE_QA=PASS (fresh extracted archive and isolated MySQL)'
echo 'PRODUCTION=NOT_MODIFIED'

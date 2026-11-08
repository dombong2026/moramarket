#!/usr/bin/env bash
# CI-only, non-production, self-contained Moramarket install candidate ZIP.
# Never writes to a webroot, changes hosting, or connects to a real database.
set -Eeuo pipefail
umask 022

die() { printf 'PACKAGE_BUILD=BLOCKED: %s\n' "$*" >&2; exit 1; }
[[ "$(printenv GITHUB_ACTIONS || :)" == true && "$(printenv CI || :)" == true ]] ||
  die 'Only GitHub Actions isolated runners may build packages.'
[[ $# -eq 1 ]] || die 'Usage: bash support/build-qa-package.sh /runner-temp/moramarket-qa-SHA.zip'
OUTPUT="$1"
[[ "$OUTPUT" == "$RUNNER_TEMP/"* && "$OUTPUT" == *.zip ]] ||
  die 'Output ZIP must be inside RUNNER_TEMP.'
ROOT="$(git rev-parse --show-toplevel)"
[[ "$PWD" == "$ROOT" ]] || die 'Run from the repository checkout root.'
command -v rsync >/dev/null || die 'rsync is missing.'
command -v zip >/dev/null || die 'zip is missing.'
command -v sha256sum >/dev/null || die 'sha256sum is missing.'

for path in composer.lock package-lock.json composer.json vendor/autoload.php \
  public/build/manifest.json public/index.php public/installer/index.php; do
  [[ -s "$ROOT/$path" ]] || die "Missing required locked/build artifact: $path"
done
[[ ! -f "$ROOT/.env" ]] || die 'Environment secrets present in checkout; refuse packaging.'
[[ ! -f "$ROOT/storage/app/.moramarket-installed" ]] ||
  die 'Installer marker found in checkout; refuse packaging.'

SHA="$(git rev-parse HEAD)"
STAGE="$(mktemp -d "$RUNNER_TEMP/moramarket-stage.XXXXXXXX")"
trap 'rm -rf -- "$STAGE"' EXIT
PACKAGE="$STAGE/moramarket"
mkdir -p "$PACKAGE"

rsync -a \
  --exclude='/.git/' \
  --exclude='/.github/' \
  --exclude='/tests/' \
  --exclude='/support/' \
  --exclude='/docs/' \
  --exclude='/node_modules/' \
  --exclude='/.env' \
  --exclude='/.env.*' \
  --exclude='/public/hot' \
  --exclude='/public/storage' \
  --exclude='/phpunit.xml' \
  --exclude='/package.json' \
  --exclude='/package-lock.json' \
  --exclude='/vite.config.js' \
  --exclude='/.npmrc' \
  --exclude='/.gitignore' \
  --exclude='/.gitattributes' \
  --exclude='/.editorconfig' \
  --exclude='/README.md' \
  --exclude='/LICENSE.md' \
  --exclude='/storage/app/.moramarket-installed' \
  --exclude='/storage/app/installer-access.key' \
  --exclude='/storage/app/moramarket-install.lock' \
  --exclude='/storage/logs/*' \
  --exclude='/storage/framework/cache/*' \
  --exclude='/storage/framework/sessions/*' \
  --exclude='/storage/framework/views/*' \
  --exclude='/bootstrap/cache/*.php' \
  "$ROOT/" "$PACKAGE/"

mkdir -p "$PACKAGE"/storage/{app/private,app/public,framework/cache/data,framework/cache/locks,framework/sessions,framework/views,logs} \
  "$PACKAGE/bootstrap/cache" "$PACKAGE/documentation"
cp "$ROOT/docs/INSTALLATION.txt" "$PACKAGE/documentation/INSTALLATION.txt"
cp "$ROOT/LICENSE.md" "$PACKAGE/documentation/PROPRIETARY_NOTICE.txt"

printf 'Moramarket development installation candidate\nSource SHA: %s\nStatus: QA ONLY; NOT PRODUCTION APPROVED\nNo credentials or license activation are included.\n' "$SHA" > "$PACKAGE/BUILD_INFORMATION.txt"

(
  cd "$PACKAGE"
  sha256sum composer.json composer.lock public/index.php public/installer/index.php \
    vendor/autoload.php public/build/manifest.json > PACKAGE_CHECKSUMS.txt
  sha256sum -c PACKAGE_CHECKSUMS.txt >/dev/null
)

if find "$PACKAGE" -type l -print -quit | grep -q .; then
  die 'Symlinks in the buyer candidate are not permitted.'
fi
if find "$PACKAGE" -type f -name '.env' -print -quit | grep -q .; then
  die 'An environment secret was accidentally staged.'
fi

(
  cd "$STAGE"
  zip -X -q -r "$OUTPUT" moramarket
)
[[ -s "$OUTPUT" ]] || die 'The archive is empty.'
sha256sum "$OUTPUT"
echo "PACKAGE_BUILD=SUCCESS"
echo "PACKAGE_SHA=$SHA"
echo "PACKAGE_PATH=$OUTPUT"
echo 'DEPLOYMENT=NOT_PERFORMED'

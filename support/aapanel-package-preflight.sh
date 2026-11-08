#!/usr/bin/env bash
# Offline fail-closed inspection. Does not extract or deploy the QA ZIP.
set -Eeuo pipefail
die() { printf 'ISOLATED_PACKAGE_PREFLIGHT=BLOCKED: %s\n' "$*" >&2; exit 1; }
[[ $# -eq 1 ]] || die 'Usage: bash support/aapanel-package-preflight.sh /root/qa-archive.zip'
command -v python3 >/dev/null || die 'Python 3 missing.'
command -v sha256sum >/dev/null || die 'sha256sum missing.'
[[ -f "$1" && ! -L "$1" ]] || die 'Expected regular, non-symlinked ZIP.'
ZIP="$(realpath "$1")"
[[ "$ZIP" == /root/* && "$ZIP" == *.zip ]] || die 'ZIP must be inside private /root.'
SUM="$ZIP.sha256"
[[ -f "$SUM" && ! -L "$SUM" ]] || die 'Companion GitHub SHA256 file is missing.'
(
  cd "$(dirname "$ZIP")"
  sha256sum -c "$(basename "$SUM")" >/dev/null
) || die 'External SHA256 mismatch.'
SOURCE="$(cd "$(dirname "$0")" && pwd -P)"
python3 -B "$SOURCE/qa-archive-validator.py" "$ZIP" ||
  die 'Strict ZIP safety checks failed.'
sha256sum "$ZIP"
echo 'ISOLATED_PACKAGE_PREFLIGHT=PASS'
echo 'ARCHIVE_EXTRACTED=NO'
echo 'MYSQL_CONNECTION=NONE'
echo 'PRODUCTION_SITE=UNTOUCHED'
echo 'RELEASE_READY=NO'

#!/usr/bin/env bash
# Download + verify a successful GitHub Actions QA ZIP under private /root.
# Do not extract or deploy application files, modify server settings or use MySQL.
set -Eeuo pipefail
umask 077
fail() { printf 'QA_DOWNLOAD=BLOCKED: %s\n' "$*" >&2; exit 1; }
[[ $# -eq 1 && "$1" =~ ^[0-9]{6,16}$ ]] ||
  fail 'Usage: bash support/aapanel-fetch-qa-package.sh <successful GitHub Actions run ID>'
RUN="$1"
SOURCE="/root/moramarket-github"
REPO="dombong2026/moramarket"
[[ -d "$SOURCE/.git" && "$(pwd -P)" == "$SOURCE" ]] ||
  fail 'Run only inside the root-protected GitHub checkout.'
command -v gh >/dev/null || fail 'GitHub CLI missing.'
command -v sha256sum >/dev/null || fail 'sha256sum missing.'
[[ -z "$(git status --porcelain --untracked-files=normal)" ]] ||
  fail 'GitHub source has uncommitted files.'
[[ "$(git branch --show-current)" == "main" ]] || fail 'Expected main branch.'
HEAD_SHA="$(git rev-parse HEAD)"
[[ "$HEAD_SHA" =~ ^[a-f0-9]{40}$ ]] || fail 'Invalid source revision.'
echo "SOURCE_SHA=$HEAD_SHA"

METADATA="$(gh run view "$RUN" -R "$REPO" --json name,conclusion,headSha,status --jq '[.name, .conclusion, .headSha, .status] | @tsv')" ||
  fail 'Workflow verification failed.'
IFS=$'\t' read -r NAME CONCLUSION RUN_SHA STATUS <<<"$METADATA"
[[ "$NAME" == 'Laravel CI' && "$CONCLUSION" == 'success' && "$STATUS" == 'completed' ]] ||
  fail 'Requested Laravel CI run is not a successful completed run.'
[[ "$RUN_SHA" == "$HEAD_SHA" ]] ||
  fail 'Workflow commit does not match synchronized source.'

BASE="/root/Moramarket-QA-Downloads"
DEST="$BASE/$RUN_SHA"
install -d -m 0700 "$BASE"
[[ ! -e "$DEST" && ! -L "$DEST" ]] ||
  fail 'Destination already exists; refusing to overwrite.'
install -d -m 0700 "$DEST"
gh run download "$RUN" -R "$REPO" -n moramarket-QA-NOT-FOR-PRODUCTION -D "$DEST" ||
  fail 'Download unsuccessful; incomplete files may remain in destination.'

ZIP_COUNT="$(find "$DEST" -maxdepth 1 -type f -name 'moramarket-qa-*.zip' | wc -l)"
SUM_COUNT="$(find "$DEST" -maxdepth 1 -type f -name 'moramarket-qa-*.zip.sha256' | wc -l)"
[[ "$ZIP_COUNT" -eq 1 && "$SUM_COUNT" -eq 1 ]] ||
  fail 'Expected exactly one application ZIP and companion SHA256 file.'
ZIP="$(find "$DEST" -maxdepth 1 -type f -name 'moramarket-qa-*.zip' -print -quit)"
SUM="$(find "$DEST" -maxdepth 1 -type f -name 'moramarket-qa-*.zip.sha256' -print -quit)"
[[ "$(basename "$SUM")" == "$(basename "$ZIP").sha256" ]] ||
  fail 'Archive checksum filename mismatch.'
cd "$DEST"
sha256sum -c "$(basename "$SUM")" || fail 'SHA256 verification failed.'
bash "$SOURCE/support/aapanel-package-preflight.sh" "$ZIP"
printf 'QA_DOWNLOAD=VERIFIED\nSOURCE_SHA=%s\nPACKAGE_PATH=%s\n' "$RUN_SHA" "$ZIP"
printf 'APPLICATION_EXTRACTED=NO\nINSTALLER_STARTED=NO\nWEBSITE_CHANGED=NO\nPRODUCTION_RELEASE_READY=NO\n'

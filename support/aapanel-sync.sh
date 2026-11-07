#!/usr/bin/env bash
# Moramarket source-only aaPanel/GitHub synchronization.
# Never changes a production webroot, environment, database or service.
set -Eeuo pipefail
umask 077
REPO="dombong2026/moramarket"
WORKTREE="/root/moramarket-github"
BRANCH="main"
WORKFLOW="ci.yml"

die() { printf 'MORAMARKET_SYNC=BLOCKED: %s\n' "$*" >&2; exit 1; }
usage() {
    cat <<'USAGE'
Moramarket source checkout only:
  bash support/aapanel-sync.sh status
  bash support/aapanel-sync.sh sync
Requires git, authenticated GitHub CLI (gh), and successful GitHub CI.
Never deploys the website or changes aaPanel settings.
USAGE
}
if [[ $# -ne 1 ]]; then usage >&2; die 'Provide status or sync.'; fi
case "$1" in
    help|--help|-h) usage; exit 0 ;;
    status|sync) MODE="$1" ;;
    *) usage >&2; die 'Unknown mode.' ;;
esac

command -v git >/dev/null 2>&1 || die 'git is required.'
command -v gh >/dev/null 2>&1 || die 'GitHub CLI (gh) is required.'
[[ ! -L "$WORKTREE" && -d "$WORKTREE/.git" && ! -L "$WORKTREE/.git" ]] ||
    die "Clone main into $WORKTREE first."
[[ "$(cd "$WORKTREE" && pwd -P)" == "$WORKTREE" ]] ||
    die 'Symlink or unexpected checkout location.'
[[ "$(git -C "$WORKTREE" rev-parse --show-toplevel)" == "$WORKTREE" ]] ||
    die 'Unexpected Git root.'
[[ "$(git -C "$WORKTREE" symbolic-ref --quiet --short HEAD)" == "$BRANCH" ]] ||
    die 'Only main can be synchronized.'

ORIGIN="$(git -C "$WORKTREE" remote get-url origin)"
case "$ORIGIN" in
    "https://github.com/$REPO"|"https://github.com/$REPO.git"|"git@github.com:$REPO.git"|"git@github.com:$REPO") ;;
    *) die 'Origin is not the Moramarket GitHub repository.' ;;
esac
[[ -z "$(git -C "$WORKTREE" status --porcelain --untracked-files=normal)" ]] ||
    die 'Local edits or untracked files exist. No files were overwritten.'
LOCAL_SHA="$(git -C "$WORKTREE" rev-parse HEAD)"
REMOTE_SHA="$(git -C "$WORKTREE" ls-remote origin "refs/heads/$BRANCH" | awk '{print $1}')"
[[ "$REMOTE_SHA" =~ ^[0-9a-f]{40}$|^[0-9a-f]{64}$ ]] ||
    die 'Cannot determine valid GitHub main SHA.'

# Approve only the EXACT remote SHA from the latest matching push CI run.
CI_CONCLUSION="$(
    GH_PROMPT_DISABLED=1 gh api \
        "repos/$REPO/actions/workflows/$WORKFLOW/runs?branch=$BRANCH&per_page=100" \
        --jq ".workflow_runs | map(select(.head_sha == \"$REMOTE_SHA\" and .event == \"push\")) | .[0].conclusion // \"missing\""
)" || die 'GitHub Actions could not be verified. Check gh auth status.'

printf 'SOURCE_DIR=%s\nGITHUB_REPOSITORY=%s\n' "$WORKTREE" "$REPO"
printf 'LOCAL_SHA=%s\nREMOTE_SHA=%s\n' "$LOCAL_SHA" "$REMOTE_SHA"
printf 'REMOTE_CI_CONCLUSION=%s\n' "$CI_CONCLUSION"
[[ "$CI_CONCLUSION" == "success" ]] ||
    die 'Remote main does not have a successful CI push run.'

if [[ "$MODE" == "status" ]]; then
    if [[ "$LOCAL_SHA" == "$REMOTE_SHA" ]]; then
        echo 'SOURCE_STATUS=IN_SYNC'
    else
        echo 'SOURCE_STATUS=DIFFERENT'
    fi
    echo 'LIVE_WEBSITE=UNTOUCHED'
    exit 0
fi

command -v flock >/dev/null 2>&1 || die 'flock is required.'
exec 9>"$WORKTREE/.git/moramarket-sync.lock"
flock -n 9 || die 'Another synchronization is already active.'
git -C "$WORKTREE" fetch --no-tags origin "refs/heads/$BRANCH"
FETCHED_SHA="$(git -C "$WORKTREE" rev-parse FETCH_HEAD)"
[[ "$FETCHED_SHA" == "$REMOTE_SHA" ]] ||
    die 'Remote branch moved during verification. No checkout update.'
git -C "$WORKTREE" merge --ff-only FETCH_HEAD ||
    die 'Non-fast-forward history; nothing was force-reset.'
[[ "$(git -C "$WORKTREE" rev-parse HEAD)" == "$REMOTE_SHA" ]] ||
    die 'Post-sync SHA did not match the approved commit.'
printf 'MORAMARKET_SYNC=SUCCESS\nSOURCE_SHA=%s\n' "$REMOTE_SHA"
printf 'LIVE_WEBSITE=UNTOUCHED\nDATABASE=UNTOUCHED\n'
printf 'ENVIRONMENT_FILE=UNTOUCHED\nAPPLICATION_DEPENDENCIES=NOT_INSTALLED\n'

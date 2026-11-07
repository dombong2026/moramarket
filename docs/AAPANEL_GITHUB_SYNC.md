# Moramarket — GitHub to aaPanel source synchronization

**Purpose:** Keep an independent, command-ready GitHub main checkout on the aaPanel host. This is source synchronization only, not deployment.

GitHub CI has passed Laravel tests, Blade and JavaScript compilation, frontend build and a separate MySQL installer check. These results DO NOT certify production completeness or independent cPanel/aaPanel buyer installation.

## First-time setup: paste in aaPanel Terminal

~~~bash
set -Eeuo pipefail

git --version
gh --version
gh auth status

if [ ! -e /root/moramarket-github ]; then
    git clone --branch main --single-branch \
      https://github.com/dombong2026/moramarket.git \
      /root/moramarket-github
fi

cd /root/moramarket-github
bash support/aapanel-sync.sh status
~~~

If GitHub CLI is not installed or is not authenticated, install gh and run gh auth login interactively on the host, using a least-privilege account with repository and Actions read access. Do not send tokens, passwords or environment secrets through chat.

The checkout path deliberately differs from /www/wwwroot and does not replace any existing website.

## Check and sync later

~~~bash
cd /root/moramarket-github

# Report local and remote SHA plus the exact-commit GitHub CI status.
bash support/aapanel-sync.sh status

# Sync source only, after successful CI for remote main.
bash support/aapanel-sync.sh sync

# Safe code inspection commands (no production actions).
git status --short --branch
git log -1 --format='%H %cs %s'
git remote -v
~~~

## Safety guarantees

The synchronization helper refuses:
- unexpected directories or symlinks;
- incorrect GitHub origin or non-main branches;
- modified tracked or untracked files;
- main commits without successful GitHub Actions push runs;
- any branch movement while CI status is being checked;
- concurrent synchronization or non-fast-forward merges.

It only uses git fetch and git merge --ff-only. It does not reset, clean, delete or overwrite arbitrary server files. It never executes Artisan, migrations, Composer, NPM, installer, Nginx, PHP-FPM, service restart, backups or domain changes. A successful source sync prints LIVE_WEBSITE=UNTOUCHED.

If status is blocked, investigate the reason; do not force a reset or bypass the CI check. Do not point the production Nginx document root to this directory.

## Release gates still outstanding

This is an active v0.0.1 codebase, not a finished buyer release. Payment-provider acceptance, taxes, refunds, payouts, complete digital/service workflows, language translations, licensing, live email delivery, independent installation and real-browser security/accessibility acceptance remain open.

The repository is currently public even though its license is proprietary. Also, composer.lock and package-lock.json were not present in the verified main tree. Before commercial release, pin reviewed dependency versions and confirm GitHub visibility, security and packaging.

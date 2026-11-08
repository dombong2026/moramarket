# Moramarket — aaPanel Read-Only Environment and Architecture Audit

This audit is safe to run on the aaPanel host against the isolated /root/moramarket-github checkout. It prints diagnostics only and does not install software, change .env, connect to MySQL, run Artisan, alter Nginx or PHP-FPM, start services, or touch the production webroot.

## Run in aaPanel Terminal

Synchronize GitHub main after successful CI, then run the audit:

~~~bash
set -Eeuo pipefail
SOURCE="/root/moramarket-github"
bash "$SOURCE/support/aapanel-sync.sh" status
bash "$SOURCE/support/aapanel-sync.sh" sync
bash "$SOURCE/support/aapanel-readonly-audit.sh"
~~~

If the sync is blocked because a new commit has not passed CI, do not bypass it. You may still run the audit on the existing previously verified checkout:

~~~bash
bash /root/moramarket-github/support/aapanel-readonly-audit.sh
~~~

Paste the audit output for interpretation. Do NOT share environment files, SQL credentials, private keys, GitHub tokens or full server configurations. This audit deliberately does not read or display secret contents.

## Meaning of the results

| Area | Checked | Not verified |
| --- | --- | --- |
| Source identity | Git commit SHA, branch and approved remote origin | Commercial release fitness |
| Laravel | Required folders, controllers, routes, frontend entrypoints, migration folders, installer and test paths | Complete behavioral feature parity |
| PHP | Default CLI PHP and aaPanel PHP CLI candidates, compatible PHP version, extensions | Actual production PHP-FPM pool and website binding |
| Composer | Presence and version | Fully built and locked customer dependencies |
| Node/Vite | Node/npm presence, Vite 8 baseline Node 20.19+ or Node 22.12+ | Prebuilt, independently tested customer assets |
| SQL | MySQL/MariaDB daemon binary and version | Running SQL service, authentication, grants, schema or DB version |
| Permissions | Read-only stat of path modes and owners | Write access of the effective production PHP-FPM user |
| Nginx/disk | Binary version, vhost file presence, free space | HTTPS configuration, actual document root or FPM mapping |
| Release | Lockfiles, vendor directory, UI asset manifest, presence of environment marker | Independent customer installation and marketplace product completeness |

PASS means a narrowly scoped check passed, WARN is unverified or an optional dependency, and BLOCK indicates a current release-readiness blocker. An isolated GitHub source checkout is expected to lack vendor/ and public/build/ without indicating source-sync failure.

The script always reports RELEASE_READY=NO because commercial acceptance is separate from environmental inspection. The script is diagnostic and does not authorize deployment or configuration changes.

## GitHub architecture baseline (not a live host measurement)

- composer.json requires PHP 8.3+ and Laravel ^13.17.
- package.json uses Vite ^8 and Tailwind CSS ^4.
- GitHub feature tests, frontend production build and isolated MySQL 8 HTTP installer tests passed in previous runs.
- Source includes admin, vendor and customer controllers; login and password reset; product/COD ordering; private media; pages/posts; policies; knowledge base; support; and admin security audit events.
- composer.lock and package-lock.json have now been committed after isolated CI resolution for the minimum supported PHP 8.3 platform; subsequent CI runs must install from those locks.
- A source clone has no vendor/autoload.php or public/build/manifest.json. The CI-built QA ZIP includes both, but independent acceptance and buyer release are still pending.
- The current repository is publicly visible while its license is proprietary. Visibility and distribution require deliberate review.

As a correction to the initial audit, Composer 2.2.6 is Composer 2 and is compatible with the major-version check; the original helper incorrectly expected the phrase 'Composer version 2.x'. The host should not upgrade system Composer merely to clear this reporting error. If Node or npm are not in the shell PATH, that does not prove they are absent from the host; prebuilt releases should not depend on a server-side Node installation.\n\n## Release acceptance prerequisites

Independently verify the actual PHP-FPM pool and extensions; safe web document root pointing to the eventual release public directory; dedicated writable runtime folders; clean customer-installation database; real SMTP password recovery; reproducible locked builds and self-contained buyer archive; backups, rollback, upgrades, legal/privacy/rights, digital entitlement delivery, payments and refunds; and real-browser/mobile accessibility. Production remains unchanged.

Official requirements: https://laravel.com/framework/docs/deployment and https://vite.dev/blog/announcing-vite8.

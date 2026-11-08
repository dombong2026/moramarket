# Moramarket — Independent aaPanel Hosting and Recovery Acceptance

STATUS: Development milestone, not a production deployment approval.

## 1. Read-only Nginx and PHP-FPM inspection

Use the existing verified, isolated GitHub checkout:

    cd /root/moramarket-github
    bash support/aapanel-sync.sh status
    bash support/aapanel-sync.sh sync
    python3 -B support/inspect-aapanel-vhost.py

The script reads only the known Moramarket aaPanel vhost config and directly named enable-php-NN.conf include files. It reports declared domain, document root and PHP-FPM upstream without dumping the whole config or changing Nginx, PHP-FPM or the website. A file existing on disk is NOT proof the currently running Nginx has loaded it; the live PHP-FPM pool and runtime are still unverified.

A root not ending with /public is a warning, NOT permission to change the vhost.

## 2. Offline QA ZIP acceptance

The last successful GitHub Actions workflow generated an artifact named moramarket-QA-NOT-FOR-PRODUCTION, containing an inner candidate ZIP and SHA256 file.

To inspect the downloaded inner ZIP without extracting, keep it in a private directory under /root and use:

    cd /root/moramarket-github
    bash support/aapanel-package-preflight.sh /root/private-qa-candidate/moramarket-qa-SOURCE_SHA.zip

Replace the example with its real path. The offline preflight only enumerates the archive, checks essential files and excludes secrets. It never unzips, initializes an app, connects to MySQL or changes the live website.

For independent aaPanel installation acceptance later, an entirely separate, non-public webroot and disposable database must be provisioned with explicit authorization. Use a dedicated PHP-FPM pool, restrictive storage permissions and a loopback-only or separately access-controlled test listener. Do not reuse the Moramarket production vhost or database. None of those setup steps is performed by this read-only milestone.

## 3. GitHub CI recovery rehearsal

The extracted QA ZIP is installed against a disposable MySQL 8 schema. The CI tests then:
- Make a consistent mysqldump backup of the freshly installed QA database.
- Restore into a second disposable schema and verify seeded user, category and migration counts.
- Run the current Laravel migration set against the restored schema for same-version idempotence.
- Simulate an additive table migration, restore the snapshot and prove the temporary table was removed.
- Confirm the original installed QA database was never changed by this recovery rehearsal.

These exercises do NOT establish compatibility from a previous product version to a future version, disaster-recovery targets or off-site backup restoration on real hosting. These are still release gates.

## 4. Installation security checks

GitHub CI also verifies private installer access keys are removed after initialization, the installer refuses post-install access, and HTTP requests for environment secrets, Composer lockfiles, vendor autoload files, private storage and internal caches return 403/404. The QA ZIP excludes environment files, Git history, tests and installer markers.

Not yet verified on real hosting: the active vhost, TLS, Nginx routing, PHP-FPM runtime user, N-1 to N migrations, interrupted installation recovery, off-site media backup, SMTP, security assessment and real-device tests.

## 5. Marketplace functionality

GitHub main adds a real, database-backed in-app notification inbox per authenticated user, read/unread filters and counts, and transactional events when support tickets change or COD orders are placed/updated. This does not enable email delivery or online payments.

Digital entitlements, payment callbacks/refunds/taxes, vendor payouts, localization/legal review, license/updater and independent buyer acceptance remain open release gates.

The /root/moramarket-github directory remains a root-protected SOURCE MIRROR only. Do not expose it as a website or change its owner/mode.

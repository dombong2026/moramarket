# Moramarket — Reproducible Dependencies and Nonproduction ZIP QA

Status: development candidate only. This workflow DOES NOT publish a release, enable payments, or authorize deployment to the live Moramarket website.

## Dependency locks and compatibility

- composer.json defines PHP 8.3.32 as its dependency-resolution platform, while allowing PHP ^8.3 as its runtime requirement. This prevents resolving a dependency that only supports PHP 8.4 or later.
- composer.lock is generated in isolated GitHub CI and committed once reviewed.
- package-lock.json is generated with Node.js 24 in GitHub CI and committed once reviewed.
- Regular CI uses composer install (never composer update) and npm ci (never npm install) to prevent dependency drift. The job verifies both lockfiles remain unchanged.
- PHP 8.3, 8.4 and 8.5 run separate Laravel test suites against the same locked dependencies.
- The temporary lock-bootstrap workflow is manual-only and does not rewrite GitHub main automatically. Dependency updates require an explicit, reviewed commit and normal CI acceptance.

## QA ZIP assembly

The qa-candidate-package CI job builds after the tests and isolated source installer job pass.

1. In a clean GitHub-hosted runner, install production-only Composer dependencies using composer install --no-dev and committed composer.lock.
2. Install JavaScript dependencies using npm ci, then run npm run build.
3. Stage an application containing vendor/ (production-only), compiled public/build/, Laravel source and the protected five-step browser installer.
4. Exclude environment secrets, developer configurations, test files, git history, private installer access keys, initialized configuration caches, and nonessential Markdown project docs.
5. Include a readable plain-text installation guide, proprietary notice, source SHA and checksums for important entrypoints.
6. Create a single-root ZIP with moramarket/ inside; inspect it for unsafe paths and leaked secrets.
7. Independently extract the ZIP into a fresh temporary directory, verify its checksums, ensure the packaged Composer autoloader and Vite manifest work, and run the entire HTTP installer flow against a disposable MySQL 8 container.
8. Only if all gates pass, upload the ZIP as a GitHub Actions artifact named moramarket-QA-NOT-FOR-PRODUCTION, retained for seven days.

No aaPanel command is run by this workflow. The QA ZIP is not a finished CodeCanyon/commercial buyer artifact and must not be deployed to a production site.

## Source synchronization remains safe

After any successful GitHub main CI run, the aaPanel command:

~~~bash
cd /root/moramarket-github
bash support/aapanel-sync.sh status
bash support/aapanel-sync.sh sync
~~~

updates only the isolated Git checkout. It does NOT download the GitHub Actions ZIP, install dependencies, run migrations or modify the live webroot.

## Remaining independent release gates

- Actual aaPanel/cPanel PHP-FPM pool, webroot, permissions and clean buyer installation on real hosting.
- Independent upgrade, migration recovery, backup restoration and disaster recovery tests.
- Real SMTP and password reset delivery; queues, scheduled tasks and supervisor acceptance.
- Provider-verified payments/refunds, tax/invoices, commissions/payouts and legally approved customer terms.
- Secure digital entitlement fulfillment and service delivery; full locale translations and native-language/legal review.
- Physical-device and browser UX, accessibility and security acceptance.
- A proper commercial license/updater and finally approved buyer package with third-party license compliance.
- The repository remains public despite proprietary license notice; review visibility.

No commercial release is approved until these are independently accepted.

# Moramarket Laravel 13 Architecture — Actual Development Scope

Project status: active v0.0.1 source development, not an independently accepted commercial marketplace release.

## Implemented and covered by GitHub CI

- Laravel 13 / PHP 8.3+, shared responsive Blade/Tailwind/Vite layout, centralized navigation, reusable table filtering and 3-dot actions.
- Authentication, self-registration of customers, password recovery broker, session regeneration, role restrictions, CSRF and route throttling.
- Administrator modules for categories, vendors, orders, CMS page/post revisions, policy publishing, knowledge articles, support cases, settings, appearance, media and Security Center audits.
- Approved vendor workspace with product editing, private media, order management, flat-rate shipping and delivery transitions.
- Customer profile, vendor application, order history, support tickets and stock-locked physical-product cash-on-delivery checkout.
- Public storefront with category/product listings, page/blog content, public policies, and searchable knowledge.
- Private storage and role/ownership checks, HTML sanitization, selected HTTP response security headers and audit event metadata.
- Standalone five-step installer for a new empty MySQL database, access-code protection and installer lockout; isolated MySQL 8 HTTP installer smoke checks in GitHub CI.

## Reusable architecture

| Layer | Primary source |
| --- | --- |
| Routing and middleware | routes/web.php, bootstrap/app.php, app/Http/Middleware |
| Authentication and normalization | app/Http/Controllers/Auth, app/Support/EmailNormalizer.php |
| Workspaces | app/Http/Controllers/Admin, Vendor, Account, Dashboard |
| Business models and services | app/Models, app/Services |
| Shared UI and navigation | resources/views/components/ui, resources/views/partials, app/Support/WorkspaceNavigation.php |
| Shared frontend assets | resources/css/app.css, resources/js/app.js |
| Storage and migrations | config/filesystems.php, config/database.php, database/migrations |
| Browser installer | public/installer/index.php |
| Testing | tests/Feature, support/qa-installer.sh, .github/workflows/ci.yml |
| Safe host diagnostics | support/aapanel-sync.sh, support/aapanel-readonly-audit.sh |

## Open gates and architectural limits

- Digital items and services are listings only; paid digital download entitlement and service booking lifecycle are not complete.
- Online payments, refunds, invoices/taxes, commissions, vendor payouts and settlement are not operational or certified.
- Thirteen language URL choices are not equivalent to completed UI/legal translations.
- SMTP, browser/mobile QA, scheduling/queues, full customer installation/upgrade/restore, security/legal/rights checks and support acceptance are incomplete.
- GitHub CI builds a nonproduction QA ZIP containing production vendor/ and compiled public/build/ and tests it in an extracted directory with isolated MySQL. No approved buyer ZIP, commercial license verification or updater exists yet.
- composer.lock and package-lock.json are committed and verified. CI uses composer install and npm ci (no dependency resolution) on the supported PHP range.
- GitHub repository is public despite proprietary licensing; review distribution/visibility.

GitHub CI is not a production-readiness certificate. Host auditing is deliberately non-invasive and cannot approve a deployment.

See docs/AAPANEL_READONLY_AUDIT.md and docs/AAPANEL_GITHUB_SYNC.md.

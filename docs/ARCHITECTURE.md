# Moramarket v0.0.1 Architecture

## Implemented

- Laravel 13 and PHP 8.3+, Tailwind CSS 4, Blade and Vite.
- Laravel authentication, CSRF, throttled login/registration/reset flows and role-scoped workspaces.
- Administrator categories, vendor approval, order review, General Settings, homepage/footer Appearance, and read-only System Health checks.
- Vendor product CRUD, image uploads and protected order management; customer profile, vendor applications and order history.
- Public catalog, stock-locked multi-vendor COD physical product checkout. Digital products and services are listing-only.
- A private image-only Media Library with role-scoped upload, view, metadata editing and deletion; product featured-media reuse and deletion safeguards. Media files use the private Laravel local disk, and controlled image streaming avoids public/storage symlinks.
- Locale-prefixed public URLs for 13 languages, language preference detection only. Full translations are not implemented.
- Standalone five-step PHP installer before Laravel bootstrap, private access key, CSRF, prepared package preflight, empty MySQL database verification, private .env generation, framework migrations, first admin, and an installer lock file.
- Automated PHPUnit checks, frontend build and CI MySQL-powered HTTP installer acceptance.

## Installer and deployment safeguards

- The 48-character private access code is stored in storage/app/installer-access.key outside the document root.
- HTTPS is required for remote installation credentials; web document root must resolve to public/.
- Installer refuses preexisting database tables and refuses to replace an existing application key.
- Install configuration is written atomically, with restrictive file permissions; do not expose the project root.
- Final installation is serialized by a private lock, and completion locks the installer.
- Migration failure after .env creation can require manual recovery. No destructive automated rollback is promised.
- Buyers must receive prebuilt vendor/ dependencies and public/build/ assets. Installer pages need no terminal command.

## Not yet implemented or accepted

- A release ZIP with independent cPanel and aaPanel buyer-installation acceptance.
- Video/YouTube featured media, full media transformations, Page/Post Manager, rich text HTML editor, configurable header, complex layout builder, and native-language translation coverage.
- Secure digital download entitlement delivery, service booking and scheduling.
- Shipping/tax rules, account ledger, refunds, commissions, vendor payouts and processor-verified payments.
- Policy/Support Centers, notifications, license checker, authenticated updater, audit trail and backup/restore UI.
- Compliance-grade buyer documentation, security review and marketplace acceptance.

## Data protection

- Public registration can create customers only; an administrator is initially created by the private installer.
- Vendor product and order mutations are ownership-scoped. Vendor approval is required before storefront publication.
- User-provided product descriptions are escaped, not rendered as untrusted HTML.
- Product image uploads are validated for safe image types and size.
- Order placements lock stock rows in a database transaction. No real-money payment gateway is active.

No production deployment to moramarket.tonsamith.com was performed by these commits.

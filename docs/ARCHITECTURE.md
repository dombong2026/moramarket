# Moramarket v0.0.1 Architecture

## Implemented application infrastructure
- Laravel 13.x and PHP 8.3+, Tailwind CSS 4 and Vite 8.
- Laravel session authentication with login, registration, logout, password-reset broker, and rate limiting.
- Three role-aware dashboard route groups sharing one responsive Blade shell.
- Administrator category create, read, update, delete with foreign-key safeguards; vendor application approvals.
- Vendor product create, read, update, delete and image uploads; vendor-specific order visibility and fulfillment status updates.
- Customer profile changes, vendor applications, and order history.
- Published, active-category, approved-vendor storefront filters.
- Session cart and atomic, stock-locked, multi-vendor cash-on-delivery checkout for physical goods.
- Internationalization locale detection and locale-prefixed public catalog URLs, without claiming completed translations.
- Automated feature tests and frontend build workflow.

## Not implemented (do not advertise as shipped)
- Buyer-facing five-step / one-click installer and administrator bootstrap wizard.
- Media Library, appearance/page builder, rich-text CMS editor, posts, pages, and footer section configuration.
- Digital entitlement/download delivery and service scheduling/fulfillment.
- Tax, shipping, refunds, accounting ledger, commissions, payouts, and provider-verified payments.
- Policy Center, Support Center, notification queue, translated content strings, multi-currency checkout.
- License Checker, automatic updater, system maintenance dashboard, auditing and backup UI.
- CodeCanyon buyer package, full legal templates and customer-installation acceptance.

## Security decisions
- Anonymous registration always creates a customer. Administrator accounts must be provisioned securely, never through an unguarded registration field.
- Vendor access requires an approved vendor relationship. Vendor product and order mutations are owner-scoped.
- Product descriptions are escaped, not rendered as unrestricted HTML.
- File uploads allow JPG/PNG/WebP only, up to 5 MB, through Laravel validation.
- No real card transactions, vendor payments, or digital entitlement fulfillment is enabled.
- Sessions and CSRF are Laravel-native; checkout locks stock rows inside database transactions.

## Delivery contract
The source repository is currently public, and all code in this branch can be read by visitors. Keep production secrets in .env files only and consider making proprietary application source private before distribution. Production at moramarket.tonsamith.com is not deployed from this code automatically.

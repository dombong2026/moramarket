# Moramarket

Moramarket is a self-hosted multi-vendor marketplace for physical products, digital listings, and services.

- Version: v0.0.1 — active development
- Author: Ton Samith — https://tonsamith.com
- Website: https://moramarket.tonsamith.com
- Product description: https://demo.tonsamith.com/moramarket
- Documentation: https://docs.tonsamith.com/moramarket
- Programming: Laravel 13, Tailwind CSS, Blade, JavaScript
- License: Proprietary commercial software. Third-party libraries retain their own licenses.

## Implemented development modules

- Shared responsive admin/vendor/customer dashboards, role authorization, authentication, system health, settings, appearance and footer visibility.
- Central navigation registry, 10-row query service, shared three-dot action menus, modals, confirmation feedback and HTTP security headers.
- Administrator Security Center with append-only model activity records and sign-in/sign-out audit metadata, without logging private field values.
- Multi-vendor product listing and cash-on-delivery checkout for physical goods, category and vendor management, customer order history.
- Five-step protected browser installer for a prebuilt release package, MySQL provisioning and initial administrator.
- Private Media Library with images and video, metadata editing, owner scopes, featured media reuse.
- Page/Post Manager: published/draft pages and posts, server-sanitized text editor with HTML source, SEO, revisions and restores.
- Policy Center: versioned policies with admin CRUD and published public read pages.
- Knowledge Base: category/article CRUD, public search, 10-item pagination and published article pages.
- Support Center: customer tickets, threaded replies, admin response and open/closed status.
- Vendor Delivery Settings: configurable per-vendor flat charges, free threshold and order delivery transitions.
- Automated Laravel feature tests across PHP 8.3, 8.4 and 8.5, locked JS/CSS builds, a fresh-archive MySQL installer test and nonproduction QA ZIP artifacts in GitHub Actions.

## Release limits

This repository is an active development build, NOT a finished CodeCanyon buyer package. Independent cPanel/aaPanel installation and upgrade, payment/legal/tax compliance, end-to-end browser review, security testing and packaging are required before release. Online payment gateways, digital entitlements, commissions/payouts, invoices/refunds, shipment carrier integrations and full 13-language translations are not active.

- Developer guidance: docs/DEVELOPMENT.md
- Safe aaPanel/GitHub source sync: docs/AAPANEL_GITHUB_SYNC.md
- Read-only aaPanel environment and architecture audit: docs/AAPANEL_READONLY_AUDIT.md
- Reproducible dependency and QA ZIP pipeline: docs/REPRODUCIBLE_RELEASE.md
- Architecture and security: docs/ARCHITECTURE.md
- Shared frontend/backend/security standard: docs/ARCHITECTURE_REUSABLE_UI.md
- Security Center and audit privacy model: docs/SECURITY_CENTER.md
- New modules: docs/CONTENT_SUPPORT_DELIVERY.md
- Roadmap: docs/ROADMAP.md

The repository is PUBLIC. Do not commit secrets, buyer credentials or real customer information.

Copyright 2026 Ton Samith. All rights reserved.

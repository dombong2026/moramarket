# Moramarket

Moramarket is a self-hosted multi-vendor marketplace for physical products, digital listings, and services.

- Version: v0.0.1 — development
- Author: Ton Samith — https://tonsamith.com
- Website: https://moramarket.tonsamith.com
- Product description: https://demo.tonsamith.com/moramarket
- Documentation: https://docs.tonsamith.com/moramarket
- Programming: Laravel 13, Tailwind CSS, Blade, JavaScript
- License: Proprietary commercial software. Third-party libraries retain their individual licenses.

## Current implementation

- Laravel 13 foundation with administrator, vendor, and customer workspaces using a shared dashboard layout.
- Public catalog, categories, physical product cart, guarded cash-on-delivery order placement, and vendor product management.
- Responsive light/dark interfaces, centered CRUD dialogs, searches/filters and 10-item table pagination.
- Administrator General Settings, editable homepage hero and footer-column visibility, read-only System Health.
- Owner-scoped private Media Library image upload/view/edit/delete, reusable featured-media selection and upload preview.
- Five-step browser installer for a prepared buyer archive, with a private access code, hosting checks, MySQL connection, site details and first-administrator provisioning.
- Automated Laravel feature tests, Tailwind build and MySQL-powered HTTP installer CI checks.

## Release status

This is development source, not yet an accepted buyer package. Customer installation archives must include Composer vendor dependencies and compiled frontend assets. Independent cPanel/aaPanel acceptance, browser QA, security review and complete documentation are still required. Online payments, digital entitlement delivery, payouts, license verification, and automatic updates are not enabled.

Developer instructions: docs/DEVELOPMENT.md
Architecture and limitations: docs/ARCHITECTURE.md
Roadmap: docs/ROADMAP.md

This repository is publicly visible. Never place production secrets or user data in the repository.

Copyright 2026 Ton Samith. All rights reserved.

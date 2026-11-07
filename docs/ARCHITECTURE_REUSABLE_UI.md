# Moramarket Shared Platform Architecture

Status: GitHub-only development. No partial-code installation or production-server tests.

## One shared frontend design system

- resources/css/app.css — one compiled Tailwind entry, unified orange/blue light/dark tokens, responsive layouts, tables, forms, buttons, sidebar, dialogs and notifications.
- resources/js/app.js — one JavaScript interaction source for theme, sidebar, modals, accessible three-dot action menus, confirmed mutations, editor, password visibility and media previews.
- resources/views/layouts/workspace.blade.php — same shell for admin, approved vendor and customer.
- app/Support/WorkspaceNavigation.php and partials/workspace-navigation.blade.php — single route registry with role-specific links and active states.
- resources/views/components/ui/action-trigger.blade.php — one reusable vertical 3-dot action trigger.
- resources/views/components/ui/action-menu.blade.php — menu surface rendered outside table scroll clipping.
- resources/views/components/ui/modal.blade.php — reusable centered dialog shell.
- resources/views/components/ui/modal-frame.blade.php — actual reusable modal frame adopted across the existing dashboard dialogs.
- resources/views/partials/confirm-dialog.blade.php — shared in-theme confirmation.
- resources/views/partials/notices.blade.php — consistent top-right success and error feedback, plus validation modal restoration.
- resources/views/partials/table-tools.blade.php and table-footer.blade.php — search, filter, Previous/Next, Go To, and ten rows per page.

All dashboard sections should reuse these components. Never display placeholder links or buttons.

## Reusable backend services

- app/Support/TableQuery.php — centralized validated search/filter, allowlisted state values, grouped SQL parameters and fixed 10-row pagination; reused in admin, vendor and customer list endpoints.
- app/Support/WorkspaceNavigation.php — all three roles share the same route contract.
- app/Services/SafeHtml.php — mandatory server-side HTML allowlist.
- app/Services/ShippingCalculator.php — consistent per-vendor shipping calculations for checkout preview and transactional order creation.
- app/Services/MediaAssetService.php — private validated uploads associated with their owner.
- Laravel route middleware, CSRF and model-scoped queries provide access control.

## Security baseline

- app/Http/Middleware/SecurityHeaders.php — no MIME sniffing, frame denial, referrer restrictions, permissions policy, restricted account-page caching, HSTS on HTTPS, and a restrictive script-src first-party Content Security Policy.
- app/Services/AuditTrail.php — centralized Eloquent model and successful authentication audit event logging. Admin-only Security Center shows metadata, never secret field values.
- Server-side validation and ownership checks are mandatory for every mutation.
- Private files are streamed only after authorization. Public content is rendered only after publication.
- Rich text must pass the allowlist sanitizer. The source editor never injects untrusted typed HTML into live browser DOM.
- One branded confirmation modal handles data-confirm forms; incorrect forms reopen the relevant dialog.

## Acceptance requirements

Passing the automated Laravel test suite, PHP syntax checks and Vite build does not establish commercial completeness. Still required: independently packaged cPanel/aaPanel fresh installs and upgrades, real-device browser and accessibility testing, security penetration testing, supported merchant payment integration, tax/refund/shipping compliance, digital entitlements, commissions/payouts, notifications, full translations, legal review, system licensing/updater/backup workflows.

No live-site deployment is authorized by GitHub commits.


### Persisted theme preference

The light/dark mode preference uses a dedicated, non-sensitive mm-theme cookie so Blade can render the correct theme before CSS loads. This **single** cookie is excluded from Laravel cookie encryption; session, authentication, remember-token and CSRF cookies retain their normal protections. User-supplied cookie values are restricted in templates to dark or light only. Browser JavaScript and server rendering share this preference.

A GitHub feature test submits the literal unencrypted cookie to verify the production browser behavior. No production server or client data is used.

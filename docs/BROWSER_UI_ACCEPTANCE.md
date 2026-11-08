# Moramarket — Browser UI Acceptance and Buyer Package Gate

Status: Development-only, no production installation approved.

## Why a new browser gate is necessary

Laravel feature tests, static Blade compilation, route listing, and npm build success
cannot prove a UI works in a browser. This independent Chromium job renders public
storefront pages, authentication, and admin/vendor/customer dashboards against an
isolated SQLite installation. It checks HTTP status, JavaScript page exceptions,
missing CSS/JS assets, page-title presence, mobile horizontal overflow, light/dark
theme state, and selected genuine interactions (modal, search, sidebar, menu,
login, password visibility, notifications).

The test creates temporary admin, vendor and customer accounts in a disposable CI
SQLite database only. It never reads production data, does not connect to aaPanel
or the public domain, and is not included in the packaged ZIP.

## Scope

Browsed routes include: homepage, catalog, category, product, cart, public page,
blog, policies, knowledge base, authentication, admin dashboard and all registered
admin management pages, vendor workspace pages, and customer account pages.
The test renders both desktop and mobile widths in light and dark modes and
captures selected screenshots and a JSON report.

Passing scripted checks does NOT establish human visual acceptance on physical
iPhone, Android or tablet screens, nor does it prove every CRUD and drag/drop
interaction. Real user-experience testing remains a release gate.

## Release candidate protection

The independent QA ZIP job is now blocked unless PHP 8.3/8.4/8.5 tests,
the isolated browser-visual job and the full standalone installer test all pass.
The artifact is named QA-NOT-FOR-PRODUCTION. It contains vendor/ and public/build/
but is not authorization to deploy.

The aaPanel host's current document root is outside Laravel public/, containing
a .user.ini file. This must be preserved until a separately authorized
deployment design explicitly addresses public/ routing and no-leak security.

## Retrieving an accepted artifact for offline inspection

With authenticated GitHub CLI on aaPanel, download the exact passed run by ID:

    gh run download RUN_ID -R dombong2026/moramarket \
      -n moramarket-QA-NOT-FOR-PRODUCTION \
      -D /root/moramarket-candidate/RUN_ID

The GitHub artifact is an outer transport archive. The above command extracts
that transport artifact into a private directory; it does NOT install the inner
Moramarket application archive. The inner moramarket-qa-*.zip must be SHA256
verified using its companion .sha256 file and the read-only offline preflight
before any separate installation is contemplated.

DO NOT extract the inner ZIP into the live webroot or execute /installer/ on
the live domain yet. The finished commercial buyer ZIP and actual host-based
acceptance remain separate gates.

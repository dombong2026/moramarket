# Moramarket v0.0.1 — Editorial, Support and Shipping

Development milestone (not yet approved for production or commercial distribution).

## Admin workspaces

- /dashboard/content/page — Page Manager
- /dashboard/content/post — Post Manager
- /dashboard/policies — Policy Center
- /dashboard/knowledge — Knowledge Base articles and categories
- /dashboard/support — ticket review, replies and status

All use Laravel's admin role guard. Tables search/filter and paginate at 10 rows per page. Create/edit/delete actions use server validation and role middleware, with centered dialogs for CRUD.

### Editor safety and publishing

Text mode offers headings, bold/italic/underline, lists, links, undo/redo, and remove-format. HTML mode accepts raw markup, but **never renders unsaved HTML in the editor**. Saved HTML is sanitized on the server with an explicit allow-list of ordinary typographic tags, headings, lists and verified HTTP/HTTPS/local-path links. Scripts, attributes, event handlers, iframes, external images and executable markup are discarded. Saving a malicious source cannot publish an executable script.

Pages/posts:
- title, excerpt, body, status, SEO title and SEO description
- featured media: none, Media Library image, Media Library video, YouTube video (validated video ID, privacy-enhanced embed), solid color
- per-type unique immutable URLs, draft/publish, versioned revisions and restore
- public /{locale}/pages, /{locale}/blog, /{locale}/page/{slug}, /{locale}/post/{slug}
- only published, past/present publication dates are publicly readable

Policies:
- admin-only create/edit/delete, draft/publish and retained body revisions
- publicly visible /policies and /policies/{slug} only for published entries
- no automatically generated legal claims; policies need real legal review before publication

Knowledge Base:
- category CRUD with deletion protection while articles are attached
- article CRUD, draft/publish, public search/category filter, 10 articles per page
- public /support-center and /support-center/article/{slug}

Tickets:
- authenticated customer ticket creation and threaded replies
- admin-only ticket status and team replies
- tickets owner-scoped, including direct URL and reply permissions
- open/waiting/closed statuses; closed tickets cannot accept messages until reopened
- messages are stored and displayed as escaped plain text, not executable HTML
- email notifications and SLA automation are not yet implemented

## Media

- JPEG, PNG, WebP images (<=5 MB)
- MP4 and WebM videos (<=30 MB)
- all uploaded media stored on Laravel's private local disk
- admin and owner-scoped streaming; published content media has a separately guarded public streaming endpoint
- images may be reused as product featured media; videos may be used as page/post featured media
- YouTube video is referenced by canonical validated video ID (no arbitrary remote URL rendering)
- deletion is refused while assigned as featured media in products, pages, or posts
- audio files, image transformations, transcoding, virus scanning and resumable uploads are not yet implemented

## Delivery

Approved vendors can configure /vendor/shipping:
- delivery enabled/paused
- per-order flat fee (free by default until configured)
- free-shipping minimum
- estimated delivery days and internal policy notes

Physical-goods checkout previews the fee separately for each vendor. The server recalculates amounts while placing the stock-locked order, and saves subtotal, shipping fee, and total as immutable order snapshots. Store orders follow pending -> processing -> shipped -> completed, or pending/processing -> cancelled with stock restoration. Shipping carrier and tracking reference are optional on dispatch.

**No shipping zones, country/carrier integrations, VAT/GST/tax calculation, return/refund engine, vendor payouts or online payments are implemented. Do not enable commercial sales until these are accepted.**

## Testing and limitations

GitHub Actions runs Laravel feature tests, the Vite/Tailwind build and a MySQL-backed installer smoke test. Passing CI is not equivalent to real-device UX, legal review, PHP shared-host permissions, or independent cPanel/aaPanel buyer acceptance. No live Moramarket deployment is performed by commits.

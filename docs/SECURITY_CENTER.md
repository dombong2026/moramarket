# Security Center: Audit Metadata

The administrator-only Security Center is available at /dashboard/security-center.

Recorded events:
- created, updated, deleted for marketplace models: categories, vendors, products, orders, images/videos, content, legal policies, help articles, tickets and delivery profiles
- successful sign-in and sign-out of authenticated users

Implemented using Eloquent event listeners registered once in AppServiceProvider. Mutation events from controllers and queued jobs are automatically recorded; individual modules should not duplicate these writes.

Data minimization:
- resource class name and ID, action, optional actor ID, event date/time
- changed **field names** only for updates; NO changed values, password hashes, full addresses, message body, email address or payment data
- no external IP address or browser fingerprint is collected
- records are append-only in the application UI, with no delete or edit actions

The UI uses the standard search/filter controls, 10-row pagination, vertical three-dot action dropdowns, and centered read-only details dialogs.

Limitations: no intrusion-detection service, web application firewall, formal retention/export policy or tamper-evident remote log store. Stronger compliance and incident-response workflows remain release gates.

No production-server tests or deployment are performed by this development milestone.

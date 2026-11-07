# Moramarket — Authentication QA and Release Gate

Authentication routes currently implemented in Laravel 13:

| Public page | Method and route | Guest-only action |
| --- | --- | --- |
| Sign in | GET /login | POST /login |
| Create account | GET /register | POST /register |
| Forgot password | GET /forgot-password | POST /forgot-password |
| Set new password | GET /reset-password/{token}?email=... | POST /reset-password |
| Log out | — | POST /logout (authenticated) |

All pages inherit the same centered layout with no storefront header/footer, icon-backed floating input labels, password visibility buttons, CSRF tokens, and light/dark preference.

## Authentication validation and security

- EmailNormalization is applied before login, registration uniqueness checks, forgot-password requests and token redemption. Case variants and whitespace cannot bypass canonical-email uniqueness.
- A new registration is always assigned the customer role even if a malicious caller posts "admin" in a role input.
- Register/reset password rules require 10+ characters including letters and digits.
- Login rotates the session ID. Logout invalidates the old session and regenerates CSRF state.
- Guest middleware redirects already-authenticated administrators to /dashboard, approved sellers to /vendor, and customers/pending vendors to /account.
- Role and ownership authorization, request throttles and platform security headers remain enforced by the shared Laravel middleware.
- Password reset uses Laravel's token broker (60-minute lifetime and one-time use) and standard notification delivery.

## Mail delivery and operational limitations

When the application's mailer is configured as "log" or "array", a real password reset email cannot be delivered. The forgot-password page shows this limitation, and its POST action returns a validation message rather than claiming an email has been sent. For configured SMTP/transactional mailers, notifications are sent by Laravel; automated QA fakes notifications and never contacts external SMTP servers. Live provider onboarding, verified sender domain, email receipt, spam handling and deliverability must be separately accepted.

## Automated verification

tests/Feature/AuthenticationFlowTest.php exercises all four guest auth pages, markup and form actions, backend validation, duplicate email case variants, password hashes, login by role, remember-me tokens, authenticated-user guest-route redirects, logout, disabled mail handling, reset link notification dispatch and invalid, valid or reused reset tokens.

No live Moramarket server or real customer account is used. A CI pass does not certify physical browser appearance, production SMTP delivery, live cookie settings, or release readiness.

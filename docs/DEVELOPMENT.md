# Moramarket developer setup

Status: development baseline, not an accepted buyer installer.

## Requirements
- PHP >=8.3 and standard Laravel extensions (Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, PDO, Session, Tokenizer, XML)
- Composer 2
- MySQL 8+ or MariaDB (SQLite is supported for tests)
- Node.js compatible with Vite 8 for building assets
- Apache or Nginx with document root pointing to public/

## Development startup
1. Run composer install on a supported developer machine.
2. Copy .env.example to .env and configure a database connection, APP_URL, APP_KEY (generate a unique key), and SMTP.
3. Run php artisan migrate and php artisan db:seed.
4. Run php artisan storage:link for uploaded product images.
5. Run npm install and npm run build.
6. Serve the public/ document root using a supported PHP runtime.

Tests use php artisan test. GitHub Actions contains the automated test/build workflow.

## Administrator provisioning
An administrator must be created through a secure setup process. The production-grade browser installer has **not** yet been implemented, so do not place the current build online for customer registration or sales. Do not insert a publicly documented default administrator password.

## Shared hosting distribution gate
Before selling, produce a verified package with Composer vendor dependencies, compiled assets, a secure browser installer, database preflight, administration setup, rollback/upgrade checks, and an acceptance matrix for cPanel and aaPanel. No shell commands may be required from end customers at that stage.

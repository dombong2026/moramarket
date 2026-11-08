# Moramarket Developer and Installer Instructions

Development version v0.0.1. Not yet an accepted public customer release.

## Developer prerequisites

- PHP 8.3+ and standard Laravel extensions including PDO MySQL.
- MySQL 8+, Composer 2, and Node.js compatible with Vite 8.
- Web server document root MUST point to the Laravel public/ directory.

## Developer setup

1. Install dependencies on a trusted development machine using composer install (from the committed composer.lock).
2. Install frontend assets with npm ci --ignore-scripts and run npm run build (from the committed package-lock.json).
3. For local development configure .env, generate APP_KEY, and migrate/seed via Artisan.
4. Run php artisan test; CI also runs the standalone installer smoke test with MySQL.

## Browser installation for a prepared buyer archive

Package the application with vendor/ and public/build/ already included. Do not upload private credentials or any developer .env. Extract outside the public document root and set the domain's document root to public/.

- Open the site and follow the redirect to /installer/.
- Open the privately generated storage/app/installer-access.key in the cPanel or aaPanel file manager and enter its code. Do not share this code.
- Step 1 verifies PHP, required extensions, storage permissions, web document root and compiled assets.
- Step 2 validates credentials and requires an empty MySQL database.
- Step 3 sets the website identity and URL.
- Step 4 creates the first administrator account with a strong password.
- Step 5 confirms that the installer has been locked and offers a link to sign in.

Installer runtime creates .env, runs migrations and seeding through the Laravel console kernel without shelling out, creates the administrator and a private installation marker, then deletes the installer access code. The Purchase Code label is informational at this stage; no licensing server is connected and no valid license is falsely claimed.

## Operational warnings

- Do not set the document root to the project root or expose vendor, .env or storage directories.
- Configure real SMTP before depending on password recovery.
- Never use an existing customer database during installation.
- Partial migrations after failure require an authorized manual recovery procedure.
- Real cPanel and aaPanel installation, upgrade, backup, PHP version and permissions acceptance remain separate from CI.
- Live payments and purchase fulfillment for digital products are disabled until separately tested and approved.


## Private Media Library

Administrators manage images at /dashboard/media. Approved vendors manage only their own images at /vendor/media. Uploaded JPEG, PNG and WebP images use the private local disk. Product forms can reuse owned images or upload a new one; uploading a new image also registers it in the Media Library. In-use images cannot be deleted until product associations are removed. The public storefront receives approved product images through an authorized Laravel route; no public/storage symlink is needed.

Video, YouTube, digital downloads and arbitrary file uploads are not available in this initial Media Library implementation.

<?php
/**
 * Moramarket one-time standalone browser installer.
 *
 * No Laravel, database session, CLI or npm process is required to display
 * the wizard. A buyer package must already include vendor/ and public/build/.
 *
 * IMPORTANT: The server document root MUST be the Laravel public/ directory.
 */

declare(strict_types=1);

define('MM_ROOT', dirname(__DIR__, 2));
define('MM_MARKER', MM_ROOT.'/storage/app/.moramarket-installed');
define('MM_INSTALL_KEY', MM_ROOT.'/storage/app/installer-access.key');

header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
session_name('mm_installer');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/installer',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

function mm_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function mm_go(int $step): never
{
    header('Location: /installer/?step='.$step, true, 303);
    exit;
}

function mm_csrf(): string
{
    return '<input type="hidden" name="_csrf" value="'.mm_e((string) $_SESSION['csrf']).'">';
}

function mm_checks(): array
{
    $directories = ['storage/app','storage/framework/cache','storage/framework/sessions','storage/framework/views','storage/logs','bootstrap/cache'];
    $checks = [
        'PHP 8.3 or newer' => version_compare(PHP_VERSION, '8.3.0', '>='),
        'PDO MySQL' => extension_loaded('pdo_mysql'),
        'Mbstring' => extension_loaded('mbstring'),
        'OpenSSL' => extension_loaded('openssl'),
        'Fileinfo' => extension_loaded('fileinfo'),
        'Ctype' => extension_loaded('ctype'),
        'XML' => extension_loaded('xml'),
        'DOM' => extension_loaded('dom'),
        'cURL' => extension_loaded('curl'),
        'Composer dependencies installed' => is_file(MM_ROOT.'/vendor/autoload.php'),
        'Compiled UI assets available' => is_file(MM_ROOT.'/public/build/manifest.json'),
        '.env directory writable' => is_writable(MM_ROOT),
        'Configuration not cached' => !is_file(MM_ROOT.'/bootstrap/cache/config.php'),
        'Web document root is public/' => isset($_SERVER['DOCUMENT_ROOT']) && realpath((string) $_SERVER['DOCUMENT_ROOT']) === realpath(MM_ROOT.'/public'),
    ];
    foreach ($directories as $directory) {
        $checks['Writable '.$directory] = is_dir(MM_ROOT.'/'.$directory) && is_writable(MM_ROOT.'/'.$directory);
    }
    return $checks;
}

function mm_env_value(string $value): string
{
    // No user input is written to env files without quoting and escaping.
    return '"'.str_replace(
        ['\\', '"', '$', "\r", "\n"],
        ['\\\\', '\\"', '\\$', '', ''],
        $value
    ).'"';
}

function mm_database_connect(array $db): PDO
{
    if (! preg_match('/^[A-Za-z0-9_.:-]{1,255}$/D', (string) $db['host'])) {
        throw new RuntimeException('Enter a valid database host.');
    }
    if (! preg_match('/^[A-Za-z0-9_]{1,64}$/D', (string) $db['database'])) {
        throw new RuntimeException('The database name can contain only letters, numbers, and underscores.');
    }
    if (! preg_match('/^[A-Za-z0-9_.-]{1,128}$/D', (string) $db['username'])) {
        throw new RuntimeException('Enter a valid database user.');
    }
    $port = (int) $db['port'];
    if ($port < 1 || $port > 65535) {
        throw new RuntimeException('Enter a valid database port.');
    }
    $password = (string) $db['password'];
    if (strlen($password) > 255 || preg_match('/[\r\n\0]/', $password)) {
        throw new RuntimeException('The database password contains unsupported characters.');
    }

    $dsn = 'mysql:host='.$db['host'].';port='.$port.';dbname='.$db['database'].';charset=utf8mb4';

    try {
        return new PDO($dsn, $db['username'], $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        // Never echo a PDO DSN or raw driver error: some include credentials.
        throw new RuntimeException('Database connection failed. Check the host, database, username, password, and user privileges.');
    }
}

function mm_require_empty_database(PDO $pdo): void
{
    if ($pdo->query('SHOW TABLES')->fetch(PDO::FETCH_NUM)) {
        throw new RuntimeException('This database contains tables. Use an empty database for a new Moramarket installation.');
    }
}

function mm_unlock_key(): void
{
    if (is_file(MM_INSTALL_KEY)) {
        return;
    }
    if (! is_dir(dirname(MM_INSTALL_KEY)) || ! is_writable(dirname(MM_INSTALL_KEY))) {
        throw new RuntimeException('storage/app is not writable. Update permissions using your hosting file manager.');
    }
    $handle = @fopen(MM_INSTALL_KEY, 'x');
    if ($handle !== false) {
        chmod(MM_INSTALL_KEY, 0600);
        fwrite($handle, bin2hex(random_bytes(24)));
        fclose($handle);
    }
    if (! is_file(MM_INSTALL_KEY)) {
        throw new RuntimeException('Could not create the private installation access code.');
    }
}

function mm_render(string $title, int $step, string $content, ?string $error = null): never
{
    http_response_code($error ? 422 : 200);
    $names = [1 => 'Requirements', 2 => 'Database', 3 => 'Website', 4 => 'Administrator', 5 => 'Complete'];
    $steps = '';
    foreach ($names as $number => $name) {
        $steps .= '<span class="step'.($number === $step ? ' active' : '').'"><b>'.$number.'</b>'.mm_e($name).'</span>';
    }
    $alert = $error ? '<div class="alert" role="alert">'.mm_e($error).'</div>' : '';
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>'.mm_e($title).' · Moramarket Installer</title>
    <style>:root{font-family:system-ui,-apple-system,Segoe UI,sans-serif;color:#172238;background:#f4f6fb}*{box-sizing:border-box}body{margin:0;padding:28px 15px;min-height:100vh;display:grid;place-items:center}.wrap{width:min(740px,100%)}.brand{font-size:25px;font-weight:850;text-align:center;letter-spacing:-1px;margin-bottom:18px}.brand em{color:#f97316;font-style:normal}.card{border:1px solid #e5eaf3;border-radius:23px;background:white;padding:30px;box-shadow:0 22px 60px #2130490c}h1{font-size:25px;margin:18px 0 6px;letter-spacing:-.04em}p{color:#65738c;margin:8px 0 18px}.steps{display:flex;align-items:center;gap:7px;flex-wrap:wrap;border-bottom:1px solid #e5eaf3;padding-bottom:20px}.step{font-size:11px;color:#7d8799;display:flex;align-items:center;gap:5px}.step b{height:21px;width:21px;display:grid;place-items:center;background:#edf0f6;border-radius:50%;color:#6a7991}.step.active{color:#eb6a0d;font-weight:800}.step.active b{background:#fff0df;color:#e9680e}.field{margin:14px 0}.field label{font-size:12px;font-weight:750;display:block;margin-bottom:6px}.field input{width:100%;padding:12px;border-radius:10px;border:1px solid #d8e0e9;outline:none;background:white;color:#172238}.field input:focus{border-color:#f97316;box-shadow:0 0 0 3px #ffedd5}.grid{display:grid;grid-template-columns:1fr 1fr;gap:0 14px}.full{grid-column:1/-1}.btn{display:inline-flex;justify-content:center;align-items:center;min-width:124px;border:1px solid #e1e6ed;border-radius:10px;background:white;color:#172238;padding:11px 17px;font-weight:750;text-decoration:none;cursor:pointer}.btn.primary{border-color:#f97316;background:#f97316;color:white}.actions{display:flex;justify-content:flex-end;gap:10px;margin-top:22px}.alert{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px 15px;border-radius:10px;margin:15px 0}.info{background:#eff6ff;padding:13px;border-radius:10px;color:#1e40af;font-size:12px}.checks{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-top:18px}.check{padding:10px 12px;background:#f8fafc;border:1px solid #eef1f4;border-radius:10px;font-size:12px}.check.pass{border-left:3px solid #16a34a}.check.fail{border-left:3px solid #dc2626}.small{font-size:12px;line-height:1.6}.muted{color:#69768a}@media(max-width:570px){.card{padding:20px}.grid,.checks{grid-template-columns:1fr}.steps{gap:5px}.step{font-size:10px}}</style></head><body><main class="wrap"><div class="brand">Mora<em>market</em></div><div class="card"><nav class="steps" aria-label="Installation steps">'.$steps.'</nav><h1>'.mm_e($title).'</h1>'.$alert.$content.'</div><p class="small" style="text-align:center">Moramarket v0.0.1 · Ton Samith</p></main></body></html>';
    exit;
}

function mm_install(array $data, array $admin): void
{
    $lock = fopen(MM_ROOT.'/storage/app/moramarket-install.lock', 'c');
    if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
        throw new RuntimeException('Another installation is already running. Try again when it finishes.');
    }

    try {
        if (is_file(MM_MARKER)) {
            throw new RuntimeException('Moramarket has already been installed.');
        }
        $checks = mm_checks();
        if (in_array(false, $checks, true)) {
            throw new RuntimeException('Hosting requirements are not ready. Return to the requirements step.');
        }
        if (! is_file(MM_INSTALL_KEY)) {
            throw new RuntimeException('Installation authorization is missing.');
        }
        $existingEnv = MM_ROOT.'/.env';
        if (is_file($existingEnv) && preg_match('/^APP_KEY=(?!\s*$).+/m', (string) file_get_contents($existingEnv))) {
            throw new RuntimeException('This site already has an application key. Setup stopped to protect an existing installation.');
        }
        $pdo = mm_database_connect($data['db']);
        mm_require_empty_database($pdo);

        $site = $data['site'];
        $db = $data['db'];
        $appKey = 'base64:'.base64_encode(random_bytes(32));
        $config = [
            'APP_NAME' => mm_env_value($site['name']),
            'APP_ENV' => 'production',
            'APP_KEY' => $appKey,
            'APP_DEBUG' => 'false',
            'APP_URL' => mm_env_value($site['url']),
            'APP_LOCALE' => 'en',
            'APP_FALLBACK_LOCALE' => 'en',
            'LOG_CHANNEL' => 'stack',
            'LOG_LEVEL' => 'warning',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => mm_env_value($db['host']),
            'DB_PORT' => (string) $db['port'],
            'DB_DATABASE' => mm_env_value($db['database']),
            'DB_USERNAME' => mm_env_value($db['username']),
            'DB_PASSWORD' => mm_env_value($db['password']),
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'database',
            'FILESYSTEM_DISK' => 'local',
            'MAIL_MAILER' => 'log',
            'VITE_APP_NAME' => mm_env_value($site['name']),
        ];
        $envText = '';
        foreach ($config as $key => $value) {
            $envText .= $key.'='.$value."\n";
        }

        // Atomic, owner-private .env creation; no command invocation or shell.
        $temp = MM_ROOT.'/.env.installing.'.bin2hex(random_bytes(8));
        if (file_put_contents($temp, $envText, LOCK_EX) === false) {
            throw new RuntimeException('Could not write the .env configuration file.');
        }
        chmod($temp, 0600);
        if (! @rename($temp, $existingEnv)) {
            @unlink($temp);
            throw new RuntimeException('Could not activate the generated .env file.');
        }

        require_once MM_ROOT.'/vendor/autoload.php';
        /** @var \Illuminate\Foundation\Application $app */
        $app = require MM_ROOT.'/bootstrap/app.php';
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        $migrations = \Illuminate\Support\Facades\Artisan::call('migrate', [
            '--force' => true,
            '--no-interaction' => true,
        ]);
        if ($migrations !== 0) {
            throw new RuntimeException('Database migrations did not complete. Check private application logs.');
        }

        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => \Database\Seeders\DatabaseSeeder::class,
            '--force' => true,
            '--no-interaction' => true,
        ]);

        if (\App\Models\User::query()->exists()) {
            throw new RuntimeException('Database was modified while installing. Administrator creation was blocked.');
        }
        \App\Models\User::query()->create([
            'name' => $admin['name'],
            'email' => strtolower($admin['email']),
            'password' => $admin['password'],
            'role' => 'admin',
        ]);

        $marker = json_encode([
            'installed_at' => gmdate(DATE_ATOM),
            'version' => '0.0.1',
            'host' => parse_url($site['url'], PHP_URL_HOST),
        ], JSON_THROW_ON_ERROR);
        if (file_put_contents(MM_MARKER, $marker, LOCK_EX) === false) {
            throw new RuntimeException('Installation completed but could not create the lock marker. Fix storage permissions before continuing.');
        }
        chmod(MM_MARKER, 0600);
        @unlink(MM_INSTALL_KEY);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

try {
    if (is_file(MM_MARKER)) {
        if (! empty($_SESSION['complete'])) {
            mm_render('Installation Complete', 5, '<p>Moramarket has been installed and the installer is locked. You can now sign in with your administrator account.</p><div class="actions"><a class="btn primary" href="/login">Open Admin Login</a><a class="btn" href="/">View Marketplace</a></div>');
        }
        header('Location: /login', true, 303);
        exit;
    }

    if (! $https && !in_array(strtolower((string) ($_SERVER['HTTP_HOST'] ?? '')), ['localhost', '127.0.0.1', 'localhost:8000', '127.0.0.1:8088'], true)) {
        http_response_code(403);
        mm_render('HTTPS Required', 1, '<p>Enable HTTPS before entering an installation access code or administrator credentials. If you are behind a reverse proxy, use a secure end-to-end TLS configuration.</p>');
    }
    mm_unlock_key();
    $accessError = null;
    if (empty($_SESSION['installer_authorized'])) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!hash_equals((string) $_SESSION['csrf'], (string) ($_POST['_csrf'] ?? ''))) {
                $accessError = 'Your session expired. Reload and try again.';
            } elseif (strlen(trim((string) file_get_contents(MM_INSTALL_KEY))) === 48 && hash_equals(trim((string) file_get_contents(MM_INSTALL_KEY)), trim((string) ($_POST['access_code'] ?? '')))) {
                $_SESSION['installer_authorized'] = true;
                session_regenerate_id(true);
                mm_go(1);
            } else {
                $accessError = 'Incorrect installation access code.';
            }
        }
        mm_render('Secure Setup Access', 1,
            '<p>Open <strong>storage/app/installer-access.key</strong> using your hosting file manager, then enter the private code shown in that file. This protects first-time administrator creation from unauthorized visitors.</p>'.
            '<form method="POST">'.mm_csrf().'<div class="field"><label for="access-code">Installation Access Code</label><input id="access-code" name="access_code" autocomplete="off" type="password" required></div><div class="actions"><button class="btn primary" type="submit">Unlock Installer</button></div></form>',
            $accessError);
    }

    $step = max(1, min(5, (int) ($_GET['step'] ?? 1)));
    if ($step >= 3 && empty($_SESSION['installer_db'])) {
        mm_go(2);
    }
    if ($step >= 4 && empty($_SESSION['installer_site'])) {
        mm_go(3);
    }
    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (! hash_equals((string) $_SESSION['csrf'], (string) ($_POST['_csrf'] ?? ''))) {
            $error = 'Your installer session has expired. Reload the page.';
        } elseif ($step === 2) {
            try {
                $db = [
                    'host' => trim((string) ($_POST['host'] ?? '')),
                    'port' => (int) ($_POST['port'] ?? 3306),
                    'database' => trim((string) ($_POST['database'] ?? '')),
                    'username' => trim((string) ($_POST['username'] ?? '')),
                    'password' => (string) ($_POST['password'] ?? ''),
                ];
                $pdo = mm_database_connect($db);
                mm_require_empty_database($pdo);
                $_SESSION['installer_db'] = $db;
                mm_go(3);
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        } elseif ($step === 3) {
            $name = trim((string) ($_POST['name'] ?? ''));
            $url = trim((string) ($_POST['url'] ?? ''));
            $parsed = parse_url($url);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
                $error = 'Website name must be between 2 and 80 characters.';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string) ($parsed['scheme'] ?? '')), ['https', 'http'], true)
                || (strtolower((string) ($parsed['scheme'] ?? '')) !== 'https' && !in_array($parsed['host'] ?? '', ['localhost', '127.0.0.1'], true))
                || isset($parsed['user']) || isset($parsed['pass']) || isset($parsed['query']) || isset($parsed['fragment'])
                || isset($parsed['path']) && !in_array($parsed['path'], ['', '/'], true)) {
                $error = 'Enter a valid HTTPS website URL, without paths or query strings.';
            } else {
                $_SESSION['installer_site'] = ['name' => $name, 'url' => rtrim($url, '/')];
                mm_go(4);
            }
        } elseif ($step === 4) {
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            if (mb_strlen($name) < 2 || mb_strlen($name) > 160) {
                $error = 'Administrator name must be between 2 and 160 characters.';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
                $error = 'Enter a valid administrator email.';
            } elseif (strlen($password) < 12 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
                $error = 'Password must contain at least 12 characters, with uppercase and lowercase letters and numbers.';
            } elseif (! hash_equals($password, (string) ($_POST['password_confirmation'] ?? ''))) {
                $error = 'Passwords do not match.';
            } else {
                try {
                    mm_install(['db' => $_SESSION['installer_db'], 'site' => $_SESSION['installer_site']], [
                        'name' => $name, 'email' => $email, 'password' => $password,
                    ]);
                    unset($_SESSION['installer_db'], $_SESSION['installer_site'], $_SESSION['installer_authorized']);
                    session_regenerate_id(true);
                    $_SESSION['complete'] = true;
                    mm_go(5);
                } catch (Throwable $exception) {
                    error_log('[moramarket-installer] Installation failed: '.$exception->getMessage());
                    $error = 'Installation could not finish. Inspect server application logs. If .env or database tables were created, recovery must be completed by the site owner; setup will not overwrite them.';
                }
            }
        }
    }

    if ($step === 1) {
        $checks = mm_checks();
        $rows = '';
        foreach ($checks as $name => $pass) {
            $rows .= '<div class="check '.($pass ? 'pass' : 'fail').'">'.($pass ? '✓' : '✕').' '.mm_e($name).'</div>';
        }
        $ready = !in_array(false, $checks, true);
        mm_render('Hosting Requirements', 1,
            '<p>Preflight for a genuine Laravel 13 installation. Server document root must point to <strong>public/</strong>. The buyer package must include Composer dependencies and compiled Tailwind assets.</p><div class="checks">'.$rows.'</div>'.
            '<div class="actions">'.($ready ? '<a href="/installer/?step=2" class="btn primary">Continue to Database</a>' : '<a class="btn" href="/installer/?step=1">Check Again</a>').'</div>');
    }

    if ($step === 2) {
        $db = $_SESSION['installer_db'] ?? ['host' => '127.0.0.1', 'port' => 3306, 'database' => '', 'username' => '', 'password' => ''];
        mm_render('Connect Database', 2,
            '<p>Use a newly created, empty MySQL database. Database changes are not made until the final installation step.</p>'.
            '<form method="POST" action="/installer/?step=2">'.mm_csrf().'<div class="grid">'.
            '<div class="field"><label for="db-host">Host</label><input required id="db-host" name="host" value="'.mm_e((string)$db['host']).'"></div>'.
            '<div class="field"><label for="db-port">Port</label><input required id="db-port" name="port" type="number" min="1" max="65535" value="'.(int)$db['port'].'"></div>'.
            '<div class="field"><label for="db-name">Database name</label><input required id="db-name" name="database" value="'.mm_e((string)$db['database']).'"></div>'.
            '<div class="field"><label for="db-user">Database username</label><input required id="db-user" name="username" value="'.mm_e((string)$db['username']).'"></div>'.
            '<div class="field full"><label for="db-password">Database password</label><input id="db-password" name="password" type="password" autocomplete="new-password" required></div></div>'.
            '<div class="actions"><a href="/installer/?step=1" class="btn">Back</a><button type="submit" class="btn primary">Verify Database</button></div></form>',
            $error);
    }

    if ($step === 3) {
        $site = $_SESSION['installer_site'] ?? ['name' => 'Moramarket', 'url' => ($https ? 'https://' : 'http://').($_SERVER['HTTP_HOST'] ?? 'localhost')];
        mm_render('Website Details', 3,
            '<p>Set the public website identity. The application key will be generated securely during final installation.</p>'.
            '<form method="POST" action="/installer/?step=3">'.mm_csrf().
            '<div class="field"><label for="site-name">Website name</label><input required id="site-name" name="name" minlength="2" maxlength="80" value="'.mm_e((string)$site['name']).'"></div>'.
            '<div class="field"><label for="site-url">Website URL</label><input required id="site-url" name="url" type="url" value="'.mm_e((string)$site['url']).'"></div>'.
            '<p class="info"><strong>Purchase Code:</strong> License-service verification is not connected in this version. Installation does not pretend to verify a purchase or grant a commercial license.</p>'.
            '<div class="actions"><a class="btn" href="/installer/?step=2">Back</a><button class="btn primary" type="submit">Continue</button></div></form>',
            $error);
    }

    if ($step === 4) {
        mm_render('Create Administrator', 4,
            '<p>Create the first administrator securely. There are no preconfigured administrator credentials. This is the final step before database migrations and installation lock.</p>'.
            '<form method="POST" action="/installer/?step=4">'.mm_csrf().
            '<div class="field"><label for="admin-name">Administrator name</label><input required id="admin-name" name="name" minlength="2" maxlength="160" autocomplete="name"></div>'.
            '<div class="field"><label for="admin-email">Administrator email</label><input required id="admin-email" name="email" type="email" maxlength="190" autocomplete="email"></div>'.
            '<div class="grid"><div class="field"><label for="admin-password">Password</label><input required id="admin-password" name="password" type="password" minlength="12" autocomplete="new-password"></div>'.
            '<div class="field"><label for="admin-password-confirm">Confirm password</label><input required id="admin-password-confirm" name="password_confirmation" type="password" minlength="12" autocomplete="new-password"></div></div>'.
            '<div class="actions"><a class="btn" href="/installer/?step=3">Back</a><button type="submit" class="btn primary">Install Moramarket</button></div></form>',
            $error);
    }

    mm_go(1);
} catch (Throwable $exception) {
    error_log('[moramarket-installer] Fatal: '.$exception->getMessage());
    mm_render('Installer Unavailable', 1,
        '<p>Setup is unavailable. Check that the private storage directory exists, is writable and is not exposed through the website. Review server logs for details.</p>');
}

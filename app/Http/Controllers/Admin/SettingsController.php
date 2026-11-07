<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    private function current(): array
    {
        return SiteSetting::query()->pluck('value', 'key')->all();
    }

    public function general()
    {
        return view('dashboard.settings', ['settings' => $this->current()]);
    }

    public function saveGeneral(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'min:2', 'max:80'],
            'site_tagline' => ['required', 'string', 'max:160'],
            'support_email' => ['nullable', 'email:rfc', 'max:190'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
            if (! array_key_exists('support_email', $data)) {
                SiteSetting::updateOrCreate(['key' => 'support_email'], ['value' => '']);
            }
        });

        return back()->with('success', 'General settings saved.');
    }

    public function appearance()
    {
        return view('dashboard.appearance', ['settings' => $this->current()]);
    }

    public function saveAppearance(Request $request)
    {
        $data = $request->validate([
            'hero_heading' => ['required', 'string', 'min:4', 'max:160'],
            'hero_description' => ['required', 'string', 'min:5', 'max:300'],
            'footer_brand_enabled' => ['required', 'boolean'],
            'footer_market_enabled' => ['required', 'boolean'],
            'footer_account_enabled' => ['required', 'boolean'],
            'footer_languages_enabled' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                SiteSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
            }
        });

        return back()->with('success', 'Appearance settings saved.');
    }

    public function health()
    {
        $checks = [
            'PHP 8.3+' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'PDO MySQL' => extension_loaded('pdo_mysql'),
            'Mbstring' => extension_loaded('mbstring'),
            'OpenSSL' => extension_loaded('openssl'),
            'Fileinfo' => extension_loaded('fileinfo'),
            'Application key configured' => filled(config('app.key')),
            'Debugging disabled' => !config('app.debug'),
            'Storage writable' => is_writable(storage_path('app')) && is_writable(storage_path('logs')),
            'UI assets built' => is_file(public_path('build/manifest.json')),
            'Installer locked' => is_file(storage_path('app/.moramarket-installed')),
            'Web root separate from app root' => realpath(public_path()) !== realpath(base_path()),
        ];
        try {
            DB::select('SELECT 1 AS healthy');
            $checks['Database connected'] = true;
        } catch (\Throwable $exception) {
            $checks['Database connected'] = false;
        }

        return view('dashboard.system-health', [
            'checks' => $checks,
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
        ]);
    }
}

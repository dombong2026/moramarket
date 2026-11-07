<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectLocale
{
    public const SUPPORTED = ['en', 'zh', 'ko', 'ja', 'de', 'km', 'vi', 'th', 'id', 'ms', 'ar', 'fr', 'es'];

    public function handle(Request $request, Closure $next): Response
    {
        $routeLocale = $request->route('locale');
        $saved = $request->hasSession() ? $request->session()->get('locale') : null;
        $locale = in_array($routeLocale, self::SUPPORTED, true) ? $routeLocale : null;

        if (! $locale && in_array($saved, self::SUPPORTED, true)) {
            $locale = $saved;
        }

        if (! $locale) {
            $country = strtoupper($request->header('CF-IPCountry', ''));
            $countries = [
                'KH' => 'km', 'CN' => 'zh', 'TW' => 'zh', 'HK' => 'zh',
                'KR' => 'ko', 'JP' => 'ja', 'DE' => 'de', 'AT' => 'de',
                'VN' => 'vi', 'TH' => 'th', 'ID' => 'id', 'MY' => 'ms',
                'FR' => 'fr', 'ES' => 'es', 'SA' => 'ar', 'AE' => 'ar',
            ];
            $locale = $countries[$country] ?? null;
        }

        if (! $locale) {
            foreach ($request->getLanguages() as $language) {
                $short = strtolower(substr($language, 0, 2));
                if (in_array($short, self::SUPPORTED, true)) {
                    $locale = $short;
                    break;
                }
            }
        }

        $locale ??= 'en';
        app()->setLocale($locale);
        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }
}

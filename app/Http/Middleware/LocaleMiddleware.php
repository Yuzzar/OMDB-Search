<?php

namespace App\Http\Middleware;

use Closure;

class LocaleMiddleware
{
    public function handle($request, Closure $next)
    {
        $locale = session('locale', config('app.locale', 'en'));
        $availableLocales = config('app.available_locales', ['en']);

        if (!in_array($locale, $availableLocales)) {
            $locale = config('app.fallback_locale', 'en');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    /**
     * Sets locale for API requests from query (?locale=ru|en|kz),
     * Accept-Language header, or falls back to Russian.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = ['ru', 'en', 'kz'];

        $locale = (string) $request->query('locale', '');

        if ($locale === '') {
            $header = strtolower((string) $request->header('Accept-Language', ''));
            $locale = substr($header, 0, 2);
        }

        if ($locale === 'kk') {
            $locale = 'kz';
        }

        if (! in_array($locale, $allowed, true)) {
            $locale = 'ru';
        }

        app()->setLocale($locale);
        config(['app.locale' => $locale]);

        return $next($request);
    }
}

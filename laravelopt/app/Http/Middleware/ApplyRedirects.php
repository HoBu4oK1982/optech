<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Redirect;

/**
 * Применяет активные редиректы из таблицы redirects.
 * Регистрируется в группе 'web'. Для Next.js-фронта не обязателен —
 * там редиректы обычно берутся из API в next.config/middleware.
 */
class ApplyRedirects
{
    public function handle(Request $request, Closure $next)
    {
        $path = '/' . ltrim($request->getPathInfo(), '/');

        $redirect = Redirect::where('is_active', true)
            ->where('from_url', $path)
            ->first();

        if ($redirect) {
            $redirect->increment('hits');
            $redirect->forceFill(['last_hit_at' => now()])->save();
            return redirect($redirect->to_url, $redirect->status_code);
        }

        return $next($request);
    }
}

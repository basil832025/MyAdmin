<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidatePaginationPage
{
    private function reject()
    {
        return response()->view(front_view('errors.lightweight-404'), [], 404);
    }

    public function handle(Request $request, Closure $next)
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        // Skip admin/Filament area.
        if ($request->is('admin/*') || $request->is('admin')) {
            return $next($request);
        }

        $raw = $request->query('page');
        if ($raw === null) {
            return $next($request);
        }

        if (is_array($raw)) {
            return $this->reject();
        }

        $raw = trim((string) $raw);
        if ($raw === '' || ! ctype_digit($raw)) {
            return $this->reject();
        }

        // Must be >= 1. ("0", "00" are invalid)
        if ((int) $raw < 1) {
            return $this->reject();
        }

        // Protect from integer overflow: treat values > PHP_INT_MAX as invalid.
        // Compare as strings to avoid numeric overflow.
        $max = (string) PHP_INT_MAX;
        $rawNoZeros = ltrim($raw, '0');
        if ($rawNoZeros === '') {
            return $this->reject();
        }

        if (strlen($rawNoZeros) > strlen($max)) {
            return $this->reject();
        }
        if (strlen($rawNoZeros) === strlen($max) && strcmp($rawNoZeros, $max) > 0) {
            return $this->reject();
        }

        $page = (int) $rawNoZeros;
        $maxPage = max(1, (int) config('app.max_pagination_page', 1000));

        if ($page > $maxPage) {
            return $this->reject();
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        if (! Schema::hasTable('redirects')) {
            return $next($request);
        }

        $originalPath = '/'.ltrim($request->getPathInfo(), '/');
        $path = $originalPath === '/' ? '/' : rtrim($originalPath, '/');

        if ($this->isLegacyWordpressQuery($request) || $path === '/index.php') {
            return redirect()->to(url('/'), 301);
        }

        if (preg_match('#^/(wp-admin|wp-content|wp-includes|wp-json)(/|$)#', $path) === 1) {
            abort(410);
        }

        if ($path === '/get-the-sensation' || str_starts_with($path, '/get-the-sensation/')) {
            return redirect()->to(url('/'), 301);
        }

        $redirect = Redirect::query()
            ->where('is_active', true)
            ->where('from_path', $path)
            ->first();

        if ($redirect) {
            $target = str_starts_with($redirect->to_path, 'http')
                ? $redirect->to_path
                : url($redirect->to_path);

            return redirect()->away($target, $redirect->status_code);
        }

        if ($originalPath !== $path) {
            $query = $request->getQueryString();

            return redirect()->to($query ? $path.'?'.$query : $path, 301);
        }

        return $next($request);
    }

    private function isLegacyWordpressQuery(Request $request): bool
    {
        foreach (['page_id', 'p'] as $key) {
            $value = $request->query($key);

            if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
                return true;
            }
        }

        return false;
    }
}

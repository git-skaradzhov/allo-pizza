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

        $path = '/'.ltrim($request->path(), '/');

        if ($path !== '/') {
            $path = rtrim($path, '/');
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

        return $next($request);
    }
}

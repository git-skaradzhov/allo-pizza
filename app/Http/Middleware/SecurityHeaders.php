<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (config('security.headers', []) as $name => $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }

            if ($name === 'Strict-Transport-Security' && ! $this->shouldSendHsts($request)) {
                continue;
            }

            $response->headers->set($name, $value);
        }

        return $response;
    }

    private function shouldSendHsts(Request $request): bool
    {
        return $request->secure() || app()->environment('production');
    }
}

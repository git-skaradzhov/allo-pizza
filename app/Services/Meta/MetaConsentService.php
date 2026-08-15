<?php

namespace App\Services\Meta;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class MetaConsentService
{
    public const GRANTED = 'granted';

    public const DENIED = 'denied';

    public function cookieName(): string
    {
        return (string) config('meta.consent.cookie', 'marketing_consent');
    }

    public function status(?Request $request = null): ?string
    {
        $value = ($request ?? request())->cookie($this->cookieName());

        if ($value === self::GRANTED || $value === self::DENIED) {
            return $value;
        }

        return null;
    }

    public function hasGranted(?Request $request = null): bool
    {
        return $this->status($request) === self::GRANTED;
    }

    public function makeCookie(bool $granted, ?Request $request = null): Cookie
    {
        $request ??= request();

        return cookie(
            $this->cookieName(),
            $granted ? self::GRANTED : self::DENIED,
            (int) config('meta.consent.lifetime_minutes', 365 * 24 * 60),
            '/',
            config('session.domain'),
            app()->environment('production') || $request->secure(),
            true,
            false,
            'lax',
        );
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Meta\MetaConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CookieConsentController extends Controller
{
    public function store(Request $request, MetaConsentService $consentService): JsonResponse
    {
        $validated = $request->validate([
            'analytics' => ['required', 'boolean'],
            'marketing' => ['required', 'boolean'],
        ]);

        return response()
            ->json([
                'ok' => true,
                'marketing' => $validated['marketing'] ? MetaConsentService::GRANTED : MetaConsentService::DENIED,
            ])
            ->withCookie($consentService->makeCookie((bool) $validated['marketing'], $request));
    }
}

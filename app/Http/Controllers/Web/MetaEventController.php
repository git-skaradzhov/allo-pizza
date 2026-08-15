<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Meta\MetaConsentService;
use App\Services\Meta\MetaConversionsApiService;
use App\Services\Meta\MetaEventFactory;
use App\Services\Meta\MetaEventTokenService;
use App\Services\Meta\MetaPageEventRegistrar;
use App\Services\Meta\MetaUserDataBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaEventController extends Controller
{
    public function store(
        Request $request,
        MetaConsentService $consentService,
        MetaEventTokenService $tokenService,
        MetaConversionsApiService $conversionsApi,
        MetaEventFactory $eventFactory,
        MetaUserDataBuilder $userDataBuilder,
        MetaPageEventRegistrar $pageEvents,
    ): JsonResponse {
        $validated = $request->validate([
            'token' => ['required_without:tokens', 'nullable', 'string'],
            'tokens' => ['required_without:token', 'nullable', 'array', 'max:5'],
            'tokens.*' => ['string'],
        ]);

        $tokens = $validated['tokens'] ?? [];

        if (! empty($validated['token'])) {
            $tokens[] = $validated['token'];
        }

        $allowed = config('meta.allowed_page_events', ['PageView', 'ViewContent', 'InitiateCheckout']);
        $sent = 0;

        foreach (array_unique($tokens) as $token) {
            $payload = $tokenService->decode($token);

            if ($payload === null) {
                return response()->json(['message' => 'Invalid event token.'], 422);
            }

            if ($payload['event_name'] === 'Purchase' || ! in_array($payload['event_name'], $allowed, true)) {
                return response()->json(['message' => 'Event is not allowed.'], 422);
            }

            if (! $consentService->hasGranted($request)) {
                continue;
            }

            if ($pageEvents->wasEventSent($payload['event_id'])) {
                continue;
            }

            $event = $eventFactory->makeEvent(
                $payload['event_name'],
                $payload['event_id'],
                $payload['event_source_url'],
                $payload['custom_data'],
                $userDataBuilder->fromRequest($request),
            );

            if ($conversionsApi->send($event)) {
                $pageEvents->markEventSent($payload['event_id']);
                $sent++;
            }
        }

        return response()->json(['ok' => true, 'sent' => $sent]);
    }
}

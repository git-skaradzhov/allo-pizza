<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Meta\MetaConsentService;
use App\Services\Meta\MetaConversionsApiService;
use App\Services\Meta\MetaEventFactory;
use App\Services\Meta\MetaPageEventRegistrar;
use App\Services\Meta\MetaUserDataBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhoneRevealController extends Controller
{
    public const SESSION_EVENT_ID = 'phone_reveal_event_id';

    public function store(
        Request $request,
        MetaConsentService $consentService,
        MetaConversionsApiService $conversionsApi,
        MetaEventFactory $eventFactory,
        MetaUserDataBuilder $userDataBuilder,
        MetaPageEventRegistrar $pageEvents,
    ): JsonResponse {
        $eventId = $request->session()->get(self::SESSION_EVENT_ID);

        if (! is_string($eventId) || $eventId === '') {
            $eventId = $eventFactory->newEventId();
            $request->session()->put(self::SESSION_EVENT_ID, $eventId);
        }

        $sent = false;

        if ($consentService->hasGranted($request) && ! $pageEvents->wasEventSent($eventId)) {
            $event = $eventFactory->makeEvent(
                'Contact',
                $eventId,
                $this->eventSourceUrl($request),
                [
                    'content_name' => 'phone_reveal',
                    'content_category' => 'contact',
                ],
                $userDataBuilder->fromRequest($request),
            );

            if ($conversionsApi->send($event)) {
                $pageEvents->markEventSent($eventId);
                $sent = true;
            }
        }

        return response()->json([
            'ok' => true,
            'event_id' => $eventId,
            'sent' => $sent,
        ]);
    }

    protected function eventSourceUrl(Request $request): string
    {
        $candidates = [
            $request->input('event_source_url'),
            $request->headers->get('referer'),
            $request->fullUrl(),
        ];

        $allowedHosts = array_values(array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            $request->getHost(),
        ]));

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            $host = parse_url($candidate, PHP_URL_HOST);

            if (is_string($host) && in_array($host, $allowedHosts, true)) {
                return $candidate;
            }
        }

        return (string) config('app.url');
    }
}

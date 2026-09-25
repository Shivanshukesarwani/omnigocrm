<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\BillingWebhookService;

class PostBillingWebhook implements Action
{
    public function __construct(private BillingWebhookService $service) {}

    public function process(Request $request): Response
    {
        $provider = trim((string) $request->getQueryParam('provider'));
        $raw = $request->getBodyContents();
        if ($provider === '' || $raw === '') throw new BadRequest('provider query parameter and raw JSON body are required.');
        return ResponseComposer::json($this->service->handle($provider, $raw, $request->getHeader('Stripe-Signature') ?: $request->getHeader('X-Razorpay-Signature')));
    }
}

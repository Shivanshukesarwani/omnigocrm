<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\CallWebhookService;

class PostCallWebhook implements Action
{
    public function __construct(
        private CallWebhookService $service,
    ) {}

    public function process(Request $request): Response
    {
        $body = $request->getBodyContents();

        if ($body === null || $body === '') {
            throw new BadRequest('Webhook body is required.');
        }

        $this->service->verifySignature(
            $body,
            $request->getHeader('X-OmniGoCRM-Signature'),
        );

        $payload = $request->getParsedBody();

        return ResponseComposer::json(
            $this->service->handle($payload)
        );
    }
}

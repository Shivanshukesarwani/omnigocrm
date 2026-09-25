<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\WhatsAppWebhookService;

class GetWhatsAppWebhook implements Action
{
    public function __construct(
        private WhatsAppWebhookService $service,
    ) {}

    public function process(Request $request): Response
    {
        $mode = $request->getQueryParam('hub.mode') ?? '';
        $token = $request->getQueryParam('hub.verify_token') ?? '';
        $challenge = $request->getQueryParam('hub.challenge') ?? '';

        if ($challenge === '') {
            throw new BadRequest('hub.challenge is required.');
        }

        return ResponseComposer::empty()
            ->setHeader('Content-Type', 'text/plain')
            ->writeBody($this->service->verify($mode, $token, $challenge));
    }
}

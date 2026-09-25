<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\WhatsAppWebhookService;

class PostWhatsAppWebhook implements Action
{
    public function __construct(
        private WhatsAppWebhookService $service,
    ) {}

    public function process(Request $request): Response
    {
        $body = $request->getBodyContents();

        if ($body === null || $body === '') {
            throw new BadRequest('Webhook body is required.');
        }

        $this->service->verifySignature(
            $body,
            $request->getHeader('X-Hub-Signature-256'),
        );

        $payload = $request->getParsedBody();
        $created = $this->service->handle($payload);

        return ResponseComposer::json([
            'received' => true,
            'messagesCreated' => $created,
        ]);
    }
}

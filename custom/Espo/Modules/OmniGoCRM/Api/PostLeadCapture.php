<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\LeadCaptureGateway;

class PostLeadCapture implements Action
{
    public function __construct(
        private LeadCaptureGateway $gateway,
    ) {}

    public function process(Request $request): Response
    {
        $apiKey = trim($request->getHeader('X-OmniGoCRM-Form-Key') ?? '');

        if ($apiKey === '') {
            throw new BadRequest('X-OmniGoCRM-Form-Key header is required.');
        }

        $data = $request->getParsedBody();

        if ($data === null) {
            throw new BadRequest('A JSON payload is required.');
        }

        return ResponseComposer::json(
            $this->gateway->capture($apiKey, $data)
        );
    }
}

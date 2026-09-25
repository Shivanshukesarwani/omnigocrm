<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Modules\OmniGoCRM\Services\LeadSourceWebhookService;

class GetMetaLeadWebhook implements Action
{
    public function __construct(
        private LeadSourceWebhookService $service,
    ) {}

    public function process(Request $request): Response
    {
        $params = $request->getQueryParams();

        return ResponseComposer::empty()->writeBody(
            $this->service->verifyMeta(
                (string) ($params['hub_mode'] ?? ''),
                (string) ($params['hub_verify_token'] ?? ''),
                (string) ($params['hub_challenge'] ?? ''),
            )
        );
    }
}

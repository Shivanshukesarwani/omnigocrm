<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\BillingService;

class GetBillingEntitlements implements Action
{
    public function __construct(private BillingService $service) {}

    public function process(Request $request): Response
    {
        $workspaceId = $request->getQueryParam('workspaceId');
        

        return ResponseComposer::json(
            $this->service->entitlements($workspaceId ?: null)
        );
    }
}

<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\BillingService;
use Espo\Modules\OmniGoCRM\Services\WorkspaceMemberService;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;

class GetBillingEntitlements implements Action
{
    public function __construct(
        private BillingService $service,
        private WorkspaceService $workspaceService,
        private WorkspaceMemberService $memberService,
    ) {}

    public function process(Request $request): Response
    {
        $workspaceId = $this->workspaceService->currentId();

        if (!$workspaceId) {
            throw new BadRequest('Select an active OmniGoCRM workspace.');
        }

        $this->memberService->activeMembership($workspaceId);

        return ResponseComposer::json(
            $this->service->entitlements($workspaceId)
        );
    }
}

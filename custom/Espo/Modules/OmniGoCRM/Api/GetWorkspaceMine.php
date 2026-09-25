<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;

class GetWorkspaceMine implements Action
{
    public function __construct(
        private WorkspaceService $service,
    ) {}

    public function process(Request $request): Response
    {
        $activeId = (string) $this->service->currentId();

        $list = [];

        foreach ($this->service->mine() as $workspace) {
            $list[] = [
                'id' => $workspace->getId(),
                'name' => $workspace->get('name'),
                'slug' => $workspace->get('slug'),
                'plan' => $workspace->get('plan'),
                'subscriptionStatus' => $workspace->get('subscriptionStatus'),
                'trialEndsAt' => $workspace->get('trialEndsAt'),
                'active' => $workspace->getId() === $activeId,
            ];
        }

        return ResponseComposer::json(['list' => $list]);
    }
}

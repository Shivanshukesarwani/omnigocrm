<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;

class PostWorkspaceSwitch implements Action
{
    public function __construct(
        private WorkspaceService $service,
    ) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();

        if ($data === null || !isset($data->workspaceId) || !is_string($data->workspaceId)) {
            throw new BadRequest('workspaceId is required.');
        }

        $workspace = $this->service->switch(trim($data->workspaceId));

        return ResponseComposer::json([
            'accepted' => true,
            'workspaceId' => $workspace->getId(),
            'name' => $workspace->get('name'),
        ]);
    }
}

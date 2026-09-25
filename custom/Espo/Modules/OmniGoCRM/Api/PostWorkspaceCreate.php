<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;

class PostWorkspaceCreate implements Action
{
    public function __construct(
        private WorkspaceService $service,
    ) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();

        if ($data === null) {
            throw new BadRequest('A JSON payload is required.');
        }

        $name = isset($data->name) && is_string($data->name) ? $data->name : '';
        $slug = isset($data->slug) && is_string($data->slug) ? $data->slug : null;

        $workspace = $this->service->create($name, $slug);

        return ResponseComposer::json([
            'accepted' => true,
            'workspaceId' => $workspace->getId(),
            'name' => $workspace->get('name'),
            'slug' => $workspace->get('slug'),
            'plan' => $workspace->get('plan'),
            'subscriptionStatus' => $workspace->get('subscriptionStatus'),
            'trialEndsAt' => $workspace->get('trialEndsAt'),
        ]);
    }
}

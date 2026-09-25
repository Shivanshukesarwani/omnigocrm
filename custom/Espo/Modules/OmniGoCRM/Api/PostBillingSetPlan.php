<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\BillingService;

class PostBillingSetPlan implements Action
{
    public function __construct(private BillingService $service) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();
        if ($data === null) throw new BadRequest('A JSON payload is required.');

        $workspaceId = is_string($data->workspaceId ?? null) ? trim($data->workspaceId) : '';
        $plan = is_string($data->plan ?? null) ? trim($data->plan) : '';
        $status = is_string($data->status ?? null) ? trim($data->status) : 'Active';

        if ($workspaceId === '' || $plan === '') {
            throw new BadRequest('workspaceId and plan are required.');
        }

        try {
            $result = $this->service->setPlan($workspaceId, $plan, $status);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            throw new BadRequest($e->getMessage());
        }

        return ResponseComposer::json([
            'accepted' => true,
            'entitlements' => $result,
        ]);
    }
}

<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Modules\OmniGoCRM\Services\DashboardService;

class GetDashboardSummary implements Action
{
    public function __construct(
        private DashboardService $service,
    ) {}

    public function process(Request $request): Response
    {
        return ResponseComposer::json($this->service->summary());
    }
}

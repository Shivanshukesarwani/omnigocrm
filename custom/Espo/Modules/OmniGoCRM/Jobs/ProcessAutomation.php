<?php

namespace Espo\Modules\OmniGoCRM\Jobs;

use Espo\Modules\OmniGoCRM\Services\AutomationService;

class ProcessAutomation
{
    public function __construct(private AutomationService $service) {}

    public function run(): void
    {
        $this->service->executeDue();
    }
}

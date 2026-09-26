<?php

namespace Espo\Modules\OmniGoCRM\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\Modules\OmniGoCRM\Services\AutomationService;

class ProcessAutomation implements JobDataLess
{
    public function __construct(private AutomationService $service) {}

    public function run(): void
    {
        $this->service->executeDue();
    }
}

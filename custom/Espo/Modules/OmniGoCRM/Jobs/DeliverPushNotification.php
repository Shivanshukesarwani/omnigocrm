<?php

namespace Espo\Modules\OmniGoCRM\Jobs;

use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data;
use Espo\Modules\OmniGoCRM\Services\FCMPushService;

class DeliverPushNotification implements Job
{
    public function __construct(private FCMPushService $service) {}

    public function run(Data $data): void
    {
        $notificationId = trim((string) $data->get('notificationId'));
        if ($notificationId !== '') $this->service->deliver($notificationId);
    }
}

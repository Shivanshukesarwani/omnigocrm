<?php

namespace Espo\Modules\OmniGoCRM\Hooks\Notification;

use Espo\Core\Job\JobSchedulerFactory;
use Espo\Core\Job\QueueName;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\Config;
use Espo\Modules\OmniGoCRM\Jobs\DeliverPushNotification;
use Espo\ORM\Entity;

class QueuePush
{
    public function __construct(
        private JobSchedulerFactory $jobSchedulerFactory,
        private Config $config,
    ) {}

    public function afterSave(Entity $entity, array $options): void
    {
        if (
            !$entity->isNew() ||
            !empty($options[SaveOption::SILENT]) ||
            !empty($options[SaveOption::NO_NOTIFICATIONS]) ||
            trim((string) $this->config->get('omniGoCRMFCMServiceAccountJson')) === '' ||
            trim((string) $entity->get('userId')) === ''
        ) return;

        $this->jobSchedulerFactory->create()
            ->setClassName(DeliverPushNotification::class)
            ->setQueue(QueueName::E0)
            ->setData(['notificationId' => $entity->getId()])
            ->schedule();
    }
}

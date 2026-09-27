<?php

namespace Espo\Modules\OmniGoCRM\Classes\Record\Workspace;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Core\Utils\Config;
use Espo\Entities\User;
use Espo\ORM\Entity;

class BeforeSave implements SaveHook
{
    public function __construct(
        private User $user,
        private Config $config,
    ) {}

    public function process(Entity $entity): void
    {
        if ($this->user->isSystem()) {
            return;
        }

        if ((bool) $this->config->get('omniGoCRMSaaSAdminBypass') && $this->user->isAdmin()) {
            return;
        }

        if ($entity->isNew()) {
            $entity->set('ownerUserId', $this->user->getId());
            $entity->set('status', $entity->get('status') ?: 'Active');
            $entity->set('plan', $entity->get('plan') ?: 'Free');
            $entity->set('subscriptionStatus', $entity->get('subscriptionStatus') ?: 'Trialing');
            return;
        }

        $ownerId = trim((string) $entity->get('ownerUserId'));
        if ($ownerId !== $this->user->getId()) {
            throw new Forbidden('Only the workspace owner can modify workspace settings.');
        }
    }
}

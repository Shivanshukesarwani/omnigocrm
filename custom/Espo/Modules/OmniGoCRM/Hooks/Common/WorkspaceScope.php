<?php

namespace Espo\Modules\OmniGoCRM\Hooks\Common;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Config;
use Espo\Entities\User;
use Espo\ORM\Entity;

class WorkspaceScope
{
    public static int $order = 1;

    public function __construct(
        private User $user,
        private Config $config,
    ) {}

    public function beforeSave(Entity $entity, array $options): void
    {
        if (!$entity->hasAttribute('omniGoCRMWorkspaceId')) {
            return;
        }

        if ($this->user->isSystem()) {
            return;
        }

        $bypass = (bool) $this->config->get('omniGoCRMSaaSAdminBypass');

        if ($bypass && $this->user->isAdmin()) {
            return;
        }

        $workspaceId = trim((string) $this->user->get('omniGoCRMCurrentWorkspaceId'));

        if ($workspaceId === '') {
            throw new Forbidden('Select an active OmniGoCRM workspace before creating or editing CRM records.');
        }

        $storedWorkspaceId = trim((string) $entity->get('omniGoCRMWorkspaceId'));

        if ($storedWorkspaceId !== '' && $storedWorkspaceId !== $workspaceId) {
            throw new Forbidden('This CRM record belongs to a different workspace.');
        }

        $entity->set('omniGoCRMWorkspaceId', $workspaceId);
    }
}

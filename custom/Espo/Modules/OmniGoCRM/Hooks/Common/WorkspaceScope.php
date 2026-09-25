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
        $workspaceAware = $entity->hasAttribute('omniGoCRMWorkspaceId');
        $legacyWorkspaceAware = in_array($entity->getEntityType(), ['AutomationRule', 'AutomationRun', 'BillingEvent'], true)
            && $entity->hasAttribute('workspaceId');

        if (!$workspaceAware && !$legacyWorkspaceAware) {
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

        $storedWorkspaceId = $workspaceAware
            ? trim((string) $entity->get('omniGoCRMWorkspaceId'))
            : trim((string) $entity->get('workspaceId'));

        if ($storedWorkspaceId !== '' && $storedWorkspaceId !== $workspaceId) {
            throw new Forbidden('This CRM record belongs to a different workspace.');
        }

        if ($workspaceAware) {
            $entity->set('omniGoCRMWorkspaceId', $workspaceId);
        }

        if ($legacyWorkspaceAware) {
            $entity->set('workspaceId', $workspaceId);
        }
    }
}

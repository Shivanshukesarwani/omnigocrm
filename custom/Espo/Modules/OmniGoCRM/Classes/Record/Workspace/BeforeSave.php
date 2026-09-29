<?php

namespace Espo\Modules\OmniGoCRM\Classes\Record\Workspace;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Core\Utils\Config;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;
use Espo\ORM\Entity;

class BeforeSave implements SaveHook
{
    public function __construct(
        private User $user,
        private Config $config,
        private EntityManager $entityManager,
        private WorkspaceService $workspaceService,
    ) {}

    public function process(Entity $entity): void
    {
        if ($entity->isNew()) {
            if (!$this->user->isSystem()) {
                $entity->set('ownerUserId', $this->user->getId());
            }

            $name = trim((string) $entity->get('name'));

            if ($name === '') {
                throw new BadRequest('Workspace name is required.');
            }

            $entity->set('slug', $this->workspaceService->uniqueSlug(
                trim((string) $entity->get('slug')) ?: $name
            ));
            $entity->set('status', $entity->get('status') ?: 'Active');
            $entity->set('plan', $entity->get('plan') ?: 'Free');
            $entity->set('subscriptionStatus', $entity->get('subscriptionStatus') ?: 'Trialing');
        }

        if ($this->user->isSystem()) return;
        if ((bool) $this->config->get('omniGoCRMSaaSAdminBypass') && $this->user->isAdmin()) return;

        if ($entity->isNew()) {
            return;
        }

        $workspaceId = (string) $entity->getId();
        if ($workspaceId === '') throw new Forbidden('Workspace ID is required.');

        $stored = $this->entityManager->getEntityById('Workspace', $workspaceId);
        if (!$stored || (string) $stored->get('ownerUserId') !== $this->user->getId()) {
            throw new Forbidden('Only the workspace owner can modify workspace settings.');
        }

        if ((string) $entity->get('ownerUserId') !== (string) $stored->get('ownerUserId')) {
            throw new Forbidden('Workspace ownership cannot be changed through record editing.');
        }
    }
}

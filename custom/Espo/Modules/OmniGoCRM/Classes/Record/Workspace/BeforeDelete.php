<?php

namespace Espo\Modules\OmniGoCRM\Classes\Record\Workspace;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\DeleteHook;
use Espo\Core\Record\DeleteParams;
use Espo\Core\Utils\Config;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\ORM\Entity;

class BeforeDelete implements DeleteHook
{
    public function __construct(
        private User $user,
        private Config $config,
        private EntityManager $entityManager,
    ) {}

    public function process(Entity $entity, DeleteParams $params): void
    {
        if ($this->user->isSystem()) return;
        if ((bool) $this->config->get('omniGoCRMSaaSAdminBypass') && $this->user->isAdmin()) return;

        $workspaceId = (string) $entity->getId();
        if ($workspaceId === '') throw new Forbidden('Workspace ID is required.');

        if ((string) $entity->get('ownerUserId') !== $this->user->getId()) {
            throw new Forbidden('Only the workspace owner can delete the workspace.');
        }

        $membership = $this->entityManager->getRDBRepository('WorkspaceMember')->where([
            'workspaceId' => $workspaceId,
            'userId' => $this->user->getId(),
            'status' => 'Active',
            'deleted' => false,
        ])->findOne();

        if (!$membership) throw new Forbidden('You are not an active member of this workspace.');
    }
}

<?php

namespace Espo\Modules\OmniGoCRM\Classes\Record\WorkspaceRecord;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\ReadHook;
use Espo\Core\Record\ReadParams;
use Espo\Core\Utils\Config;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\ORM\Entity;

class BeforeRead implements ReadHook
{
    public function __construct(
        private User $user,
        private Config $config,
        private EntityManager $entityManager,
    ) {}

    public function process(Entity $entity, ReadParams $params): void
    {
        if ($this->user->isSystem()) return;
        if ((bool) $this->config->get('omniGoCRMSaaSAdminBypass') && $this->user->isAdmin()) return;

        $workspaceId = trim((string) $this->user->get('omniGoCRMCurrentWorkspaceId'));
        $recordWorkspaceId = trim((string) (
            $entity->get('omniGoCRMWorkspaceId') ?: $entity->get('workspaceId')
        ));

        if ($workspaceId === '' || $recordWorkspaceId !== $workspaceId) {
            throw new Forbidden('This CRM record is not available in the active workspace.');
        }

        $membership = $this->entityManager->getRDBRepository('WorkspaceMember')->where([
            'workspaceId' => $workspaceId,
            'userId' => $this->user->getId(),
            'status' => 'Active',
            'deleted' => false,
        ])->findOne();

        if (!$membership) throw new Forbidden('This workspace is not available to your account.');

        $workspace = $this->entityManager->getEntityById('Workspace', $workspaceId);
        if (!$workspace || $workspace->get('status') !== 'Active') {
            throw new Forbidden('This workspace is not active.');
        }
    }
}

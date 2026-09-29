<?php

namespace Espo\Modules\OmniGoCRM\Classes\Select\Workspace;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Select\AccessControl\Filter;
use Espo\Core\Utils\Config;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\ORM\Query\SelectBuilder;

class AccessControlFilter implements Filter
{
    public function __construct(
        private User $user,
        private Config $config,
        private EntityManager $entityManager,
    ) {}

    public function apply(SelectBuilder $queryBuilder): void
    {
        $bypass = (bool) $this->config->get('omniGoCRMSaaSAdminBypass');

        if ($bypass && $this->user->isAdmin()) {
            return;
        }

        $workspaceId = trim((string) $this->user->get('omniGoCRMCurrentWorkspaceId'));

        if ($workspaceId === '') {
            $queryBuilder->where(['id' => null]);
            return;
        }

        $membership = $this->entityManager->getRDBRepository('WorkspaceMember')->where([
            'workspaceId' => $workspaceId,
            'userId' => $this->user->getId(),
            'status' => 'Active',
            'deleted' => false,
        ])->findOne();
        $workspace = $this->entityManager->getEntityById('Workspace', $workspaceId);

        if (!$membership || !$workspace || $workspace->get('status') !== 'Active') {
            $queryBuilder->where(['id' => null]);
            return;
        }

        $queryBuilder->where([
            'omniGoCRMWorkspaceId' => $workspaceId,
        ]);
    }
}

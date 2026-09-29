<?php

namespace Espo\Modules\OmniGoCRM\Classes\Select;

use Espo\Core\Select\Applier\AdditionalApplier;
use Espo\Core\Select\SearchParams;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\Core\ORM\EntityManager;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;
use Espo\ORM\Query\SelectBuilder;

class WorkspaceFilter implements AdditionalApplier
{
    public function __construct(
        private string $entityType,
        private User $user,
        private Config $config,
        private Metadata $metadata,
        private EntityManager $entityManager,
        private WorkspaceService $workspaceService,
    ) {}

    public function apply(SelectBuilder $queryBuilder, SearchParams $searchParams): void
    {
        if ($this->user->isSystem()) return;
        if ((bool) $this->config->get('omniGoCRMSaaSAdminBypass') && $this->user->isAdmin()) return;

        if ($this->entityType === 'Workspace') {
            $memberships = $this->entityManager->getRDBRepository('WorkspaceMember')->where([
                'userId' => $this->user->getId(),
                'status' => 'Active',
                'deleted' => false,
            ])->find();
            $membershipList = iterator_to_array($memberships, false);
            $workspaceIds = array_map(static fn($membership) => $membership->get('workspaceId'), $membershipList);
            if ($workspaceIds) {
                $workspaces = $this->entityManager->getRDBRepository('Workspace')->where([
                    'id' => $workspaceIds,
                    'status' => 'Active',
                    'deleted' => false,
                ])->find();
                $workspaceIds = array_map(static fn($workspace) => $workspace->getId(), iterator_to_array($workspaces, false));
            }
            $queryBuilder->where(['id' => $workspaceIds ?: ['__no_workspace__']]);
            return;
        }

        $workspaceField = $this->metadata->get(
            "entityDefs.$this->entityType.fields.omniGoCRMWorkspaceId"
        ) ? 'omniGoCRMWorkspaceId' : 'workspaceId';

        if (!$this->metadata->get("entityDefs.$this->entityType.fields.$workspaceField")) return;

        $workspaceId = $this->workspaceService->currentId() ?? '';

        if ($workspaceId !== '') {
            $membership = $this->entityManager->getRDBRepository('WorkspaceMember')->where([
                'workspaceId' => $workspaceId,
                'userId' => $this->user->getId(),
                'status' => 'Active',
                'deleted' => false,
            ])->findOne();

            if (!$membership) $workspaceId = '';
            else {
                $workspace = $this->entityManager->getEntityById('Workspace', $workspaceId);
                if (!$workspace || $workspace->get('status') !== 'Active') $workspaceId = '';
            }
        }

        $queryBuilder->where([$workspaceField => $workspaceId ?: '__no_workspace__']);
    }
}

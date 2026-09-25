<?php

namespace Espo\Modules\OmniGoCRM\Classes\Select\Workspace;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Select\AccessControl\Filter;
use Espo\Core\Utils\Config;
use Espo\Entities\User;
use Espo\ORM\Query\SelectBuilder;

class AccessControlFilter implements Filter
{
    public function __construct(
        private User $user,
        private Config $config,
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

        $queryBuilder->where([
            'omniGoCRMWorkspaceId' => $workspaceId,
        ]);
    }
}

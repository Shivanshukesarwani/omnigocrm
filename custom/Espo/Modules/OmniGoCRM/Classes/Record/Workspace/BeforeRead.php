<?php

namespace Espo\Modules\OmniGoCRM\Classes\Record\Workspace;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Config;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\Core\Record\ReadParams;

class BeforeRead
{
    public function __construct(
        private User $user,
        private Config $config,
    ) {}

    public function process(Entity $entity, ReadParams $params): void
    {
        if (!$entity->hasAttribute('omniGoCRMWorkspaceId')) {
            return;
        }

        if ($this->user->isSystem()) {
            return;
        }

        if ((bool) $this->config->get('omniGoCRMSaaSAdminBypass') && $this->user->isAdmin()) {
            return;
        }

        $current = trim((string) $this->user->get('omniGoCRMCurrentWorkspaceId'));
        $recordWorkspace = trim((string) $entity->get('omniGoCRMWorkspaceId'));

        if ($current === '' || $recordWorkspace === '' || $current !== $recordWorkspace) {
            throw new Forbidden('This CRM record is outside the active workspace.');
        }
    }
}

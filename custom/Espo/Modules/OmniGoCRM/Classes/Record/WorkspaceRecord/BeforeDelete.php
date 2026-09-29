<?php

namespace Espo\Modules\OmniGoCRM\Classes\Record\WorkspaceRecord;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\DeleteParams;
use Espo\Core\Record\Hook\DeleteHook;
use Espo\Core\Utils\Config;
use Espo\Entities\User;
use Espo\Modules\OmniGoCRM\Services\WorkspaceMemberService;
use Espo\ORM\Entity;

class BeforeDelete implements DeleteHook
{
    public function __construct(
        private User $user,
        private Config $config,
        private WorkspaceMemberService $memberService,
    ) {}

    public function process(Entity $entity, DeleteParams $params): void
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

        $this->memberService->assertCanDelete($workspaceId);
    }
}

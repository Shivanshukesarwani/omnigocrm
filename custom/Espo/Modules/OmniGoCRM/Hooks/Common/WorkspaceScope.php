<?php

namespace Espo\Modules\OmniGoCRM\Hooks\Common;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Config;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Modules\OmniGoCRM\Services\AutomationService;
use Espo\ORM\Entity;

class WorkspaceScope
{
    public static int $order = 1;

    public function __construct(
        private User $user,
        private Config $config,
        private EntityManager $entityManager,
        private AutomationService $automationService,
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

        $membership = $this->entityManager->getRDBRepository('WorkspaceMember')->where([
            'workspaceId' => $workspaceId,
            'userId' => $this->user->getId(),
            'status' => 'Active',
            'deleted' => false,
        ])->findOne();

        if (!$membership) {
            throw new Forbidden('This workspace is not available to your account.');
        }

        $workspace = $this->entityManager->getEntityById('Workspace', $workspaceId);
        if (!$workspace || $workspace->get('status') !== 'Active') {
            throw new Forbidden('This workspace is not active.');
        }

        if ($membership->get('role') === 'Viewer') {
            throw new Forbidden('Viewer role is read-only.');
        }

        if (
            $entity->getEntityType() === 'WhatsAppConversation' &&
            $entity->isAttributeChanged('assignedUserId') &&
            !in_array($membership->get('role'), ['Owner', 'Admin', 'Manager'], true)
        ) {
            throw new Forbidden('Manager access is required to assign WhatsApp conversations.');
        }

        $isSchedulingCampaign = $entity->getEntityType() === 'BroadcastCampaign' &&
            $entity->get('status') === 'Scheduled' &&
            ($entity->isNew() || $entity->isAttributeChanged('status') || $entity->isAttributeChanged('scheduledAt'));
        if ($isSchedulingCampaign && !in_array($membership->get('role'), ['Owner', 'Admin', 'Manager'], true)) {
            throw new Forbidden('Manager access is required to schedule broadcast campaigns.');
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

    public function afterSave(Entity $entity, array $options): void
    {
        $workspaceId = trim((string) ($entity->get('omniGoCRMWorkspaceId') ?: $entity->get('workspaceId')));

        if ($workspaceId === '') return;

        $event = match ($entity->getEntityType()) {
            'Lead' => $entity->isNew()
                ? 'LeadCreated'
                : ($entity->isAttributeChanged('leadStage') ? 'LeadStageChanged' : null),
            'Opportunity' => $entity->isAttributeChanged('stage') ? 'DealStageChanged' : null,
            'Call' => $entity->get('status') === 'Not Held' && (
                $entity->isNew() || $entity->isAttributeChanged('status')
            ) ? 'CallMissed' : null,
            default => null,
        };

        if ($event !== null) {
            $this->automationService->dispatch($event, $entity->getEntityType(), $entity->getId(), $workspaceId);
        }
    }
}

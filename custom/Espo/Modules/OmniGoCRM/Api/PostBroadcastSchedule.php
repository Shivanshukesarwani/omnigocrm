<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\EntityManager;
use Espo\Modules\OmniGoCRM\Services\BillingService;
use Espo\Modules\OmniGoCRM\Services\WorkspaceMemberService;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;

class PostBroadcastSchedule implements Action
{
    public function __construct(
        private EntityManager $entityManager,
        private WorkspaceService $workspaceService,
        private WorkspaceMemberService $memberService,
        private BillingService $billingService,
    ) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();

        if (!isset($data->campaignId) || !is_string($data->campaignId)) {
            throw new BadRequest('campaignId is required.');
        }

        $campaign = $this->entityManager->getEntityById(
            'BroadcastCampaign',
            trim($data->campaignId)
        );

        if (!$campaign) {
            throw new BadRequest('Broadcast campaign not found.');
        }

        $workspaceId = $this->workspaceService->currentId();

        if (!$workspaceId) {
            throw new Forbidden('Select an active workspace before scheduling broadcasts.');
        }

        $this->memberService->assertCanManageCampaigns($workspaceId);

        if ((string) $campaign->get('omniGoCRMWorkspaceId') !== $workspaceId) {
            throw new Forbidden('The broadcast campaign is not available in the active workspace.');
        }

        $queuedRecipients = $this->entityManager->getRDBRepository('BroadcastRecipient')->where([
            'campaignId' => $campaign->getId(),
            'omniGoCRMWorkspaceId' => $workspaceId,
            'status' => 'Queued',
            'deleted' => false,
        ])->count();

        if ($queuedRecipients < 1) {
            throw new BadRequest('Add at least one eligible recipient before scheduling this campaign.');
        }

        $remainingQuota = $this->billingService->broadcastRecipientsRemaining($workspaceId);

        if ($remainingQuota !== null && $queuedRecipients > $remainingQuota) {
            throw new BadRequest('The campaign exceeds the active plan’s remaining monthly WhatsApp recipient limit.');
        }

        if (!in_array($campaign->get('status'), ['Draft', 'Scheduled'], true)) {
            throw new BadRequest('Campaign is not schedulable from its current status.');
        }

        $templateName = trim((string) $campaign->get('templateName'));

        $template = $this->entityManager
            ->getRDBRepository('WhatsAppTemplate')
            ->where([
                'name' => $templateName,
                'omniGoCRMWorkspaceId' => $workspaceId,
                'status' => 'Approved',
                'active' => true,
                'deleted' => false,
            ])
            ->findOne();

        if (!$template) {
            throw new BadRequest('Campaign template must be active and Approved.');
        }

        $scheduledAt = isset($data->scheduledAt) && is_string($data->scheduledAt)
            ? trim($data->scheduledAt)
            : trim((string) $campaign->get('scheduledAt'));

        if ($scheduledAt === '' || strtotime($scheduledAt) === false) {
            throw new BadRequest('A valid scheduledAt is required.');
        }

        $campaign->setMultiple([
            'scheduledAt' => gmdate('Y-m-d H:i:s', strtotime($scheduledAt)),
            'status' => 'Scheduled',
            'languageCode' => $campaign->get('languageCode') ?: $template->get('languageCode'),
        ]);

        $this->entityManager->saveEntity($campaign);

        $count = $this->entityManager
            ->getRDBRepository('BroadcastRecipient')
            ->where([
                'campaignId' => $campaign->getId(),
                'omniGoCRMWorkspaceId' => $workspaceId,
                'deleted' => false,
            ])
            ->count();

        $campaign->set('totalRecipients', $count);
        $this->entityManager->saveEntity($campaign);

        return ResponseComposer::json([
            'accepted' => true,
            'campaignId' => $campaign->getId(),
            'status' => $campaign->get('status'),
            'scheduledAt' => $campaign->get('scheduledAt'),
            'totalRecipients' => $count,
        ]);
    }
}

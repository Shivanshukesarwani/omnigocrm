<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\EntityManager;
use Espo\Modules\OmniGoCRM\Services\WorkspaceMemberService;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;

class GetBroadcastCampaign implements Action
{
    public function __construct(
        private EntityManager $entityManager,
        private WorkspaceService $workspaceService,
        private WorkspaceMemberService $memberService,
    ) {}

    public function process(Request $request): Response
    {
        $campaignId = trim((string) $request->getQueryParam('campaignId'));
        if ($campaignId === '') throw new BadRequest('campaignId is required.');

        $workspaceId = $this->workspaceService->currentId();
        if (!$workspaceId) throw new Forbidden('Select an active workspace to view broadcasts.');
        $this->memberService->activeMembership($workspaceId);

        $campaign = $this->entityManager->getEntityById('BroadcastCampaign', $campaignId);
        if (!$campaign || (string) $campaign->get('omniGoCRMWorkspaceId') !== $workspaceId) {
            throw new Forbidden('The broadcast campaign is not available in the active workspace.');
        }

        $recipients = $this->entityManager->getRDBRepository('BroadcastRecipient')->where([
            'campaignId' => $campaignId,
            'omniGoCRMWorkspaceId' => $workspaceId,
            'deleted' => false,
        ])->order('createdAt', 'ASC')->limit(500)->find();

        $rows = [];
        $progress = ['total' => 0, 'queued' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach (['queued', 'sent', 'failed', 'skipped'] as $status) {
            $progress[$status] = $this->entityManager->getRDBRepository('BroadcastRecipient')->where([
                'campaignId' => $campaignId,
                'omniGoCRMWorkspaceId' => $workspaceId,
                'status' => ucfirst($status),
                'deleted' => false,
            ])->count();
        }
        $progress['total'] = $this->entityManager->getRDBRepository('BroadcastRecipient')->where([
            'campaignId' => $campaignId,
            'omniGoCRMWorkspaceId' => $workspaceId,
            'deleted' => false,
        ])->count();

        foreach ($recipients as $recipient) {
            $rows[] = [
                'id' => $recipient->getId(),
                'name' => $recipient->get('name'),
                'leadId' => $recipient->get('leadId'),
                'phoneNumber' => $recipient->get('phoneNumber'),
                'status' => $recipient->get('status'),
                'errorMessage' => $recipient->get('errorMessage'),
                'sentAt' => $recipient->get('sentAt'),
                'retryCount' => (int) $recipient->get('retryCount'),
            ];
        }

        return ResponseComposer::json([
            'campaign' => [
                'id' => $campaign->getId(),
                'name' => $campaign->get('name'),
                'status' => $campaign->get('status'),
                'templateName' => $campaign->get('templateName'),
                'languageCode' => $campaign->get('languageCode'),
                'scheduledAt' => $campaign->get('scheduledAt'),
                'totalRecipients' => $progress['total'],
                'sentCount' => $progress['sent'],
                'failedCount' => $progress['failed'],
                'queuedCount' => $progress['queued'],
                'skippedCount' => $progress['skipped'],
                'progress' => $progress,
            ],
            'recipients' => $rows,
        ]);
    }
}

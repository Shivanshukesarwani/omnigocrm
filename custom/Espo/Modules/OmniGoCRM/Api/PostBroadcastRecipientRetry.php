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

class PostBroadcastRecipientRetry implements Action
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
        $campaignId = isset($data->campaignId) && is_string($data->campaignId) ? trim($data->campaignId) : '';
        $recipientId = isset($data->recipientId) && is_string($data->recipientId) ? trim($data->recipientId) : '';
        if ($campaignId === '' || $recipientId === '') {
            throw new BadRequest('campaignId and recipientId are required.');
        }

        $workspaceId = $this->workspaceService->currentId();
        if (!$workspaceId) throw new Forbidden('Select an active workspace before retrying a broadcast.');
        $this->memberService->assertCanManageCampaigns($workspaceId);

        $campaign = $this->entityManager->getEntityById('BroadcastCampaign', $campaignId);
        if (!$campaign || $campaign->get('deleted') || (string) $campaign->get('omniGoCRMWorkspaceId') !== $workspaceId) {
            throw new Forbidden('The broadcast campaign is not available in the active workspace.');
        }
        if (!in_array($campaign->get('status'), ['Scheduled', 'Completed'], true)) {
            throw new BadRequest('Only scheduled or completed campaigns can retry a recipient.');
        }

        $recipient = $this->entityManager->getEntityById('BroadcastRecipient', $recipientId);
        if (
            !$recipient ||
            $recipient->get('deleted') ||
            (string) $recipient->get('campaignId') !== $campaignId ||
            (string) $recipient->get('omniGoCRMWorkspaceId') !== $workspaceId
        ) {
            throw new Forbidden('The broadcast recipient is not available in the active workspace.');
        }
        if ($recipient->get('status') !== 'Failed') {
            throw new BadRequest('Only failed recipients can be retried.');
        }
        if ((int) $recipient->get('retryCount') >= 3) {
            throw new BadRequest('This recipient has reached the three-retry limit.');
        }

        $leadId = trim((string) $recipient->get('leadId'));
        $lead = $leadId !== '' ? $this->entityManager->getEntityById('Lead', $leadId) : null;
        if (
            !$lead ||
            $lead->get('deleted') ||
            (string) $lead->get('omniGoCRMWorkspaceId') !== $workspaceId ||
            !$lead->get('whatsappOptIn') ||
            (trim((string) $lead->get('whatsappNumber')) === '' && trim((string) $recipient->get('phoneNumber')) === '')
        ) {
            throw new BadRequest('The lead must remain in this workspace, have WhatsApp opt-in, and have a WhatsApp number before retry.');
        }

        $template = $this->entityManager->getRDBRepository('WhatsAppTemplate')->where([
            'name' => trim((string) $campaign->get('templateName')),
            'omniGoCRMWorkspaceId' => $workspaceId,
            'status' => 'Approved',
            'active' => true,
            'deleted' => false,
        ])->findOne();
        if (!$template) throw new BadRequest('Campaign template must still be active and approved.');

        $remainingQuota = $this->billingService->broadcastRecipientsRemaining($workspaceId);
        if ($remainingQuota !== null && $remainingQuota < 1) {
            throw new BadRequest('The active plan monthly WhatsApp recipient limit has been reached.');
        }

        $recipient->setMultiple([
            'status' => 'Queued',
            'retryCount' => (int) $recipient->get('retryCount') + 1,
            'errorMessage' => null,
        ]);
        $this->entityManager->saveEntity($recipient);

        $campaign->setMultiple([
            'status' => 'Scheduled',
            'scheduledAt' => gmdate('Y-m-d H:i:s'),
            'completedAt' => null,
        ]);
        $this->entityManager->saveEntity($campaign);

        return ResponseComposer::json([
            'accepted' => true,
            'campaignId' => $campaignId,
            'recipientId' => $recipientId,
            'status' => 'Queued',
            'retryCount' => $recipient->get('retryCount'),
        ]);
    }
}

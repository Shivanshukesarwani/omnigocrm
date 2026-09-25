<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;

class PostBroadcastRecipientAdd implements Action
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();

        if ($data === null || !isset($data->campaignId) || !is_string($data->campaignId)) {
            throw new BadRequest('campaignId is required.');
        }

        $campaignId = trim($data->campaignId);
        $campaign = $this->entityManager->getEntityById('BroadcastCampaign', $campaignId);

        if (!$campaign) {
            throw new BadRequest('Broadcast campaign not found.');
        }

        $leadId = isset($data->leadId) && is_string($data->leadId) ? trim($data->leadId) : '';

        if ($leadId === '') {
            throw new BadRequest('leadId is required.');
        }

        $lead = $this->entityManager->getEntityById('Lead', $leadId);

        if (!$lead) {
            throw new BadRequest('Lead not found.');
        }

        $recipient = $this->entityManager
            ->getRDBRepository('BroadcastRecipient')
            ->where([
                'campaignId' => $campaignId,
                'leadId' => $leadId,
                'deleted' => false,
            ])
            ->findOne();

        if (!$recipient) {
            $recipient = $this->entityManager->getNewEntity('BroadcastRecipient');
        }

        $phone = trim((string) $lead->get('whatsappNumber'));

        $recipient->set([
            'name' => trim((string) $lead->get('name')),
            'campaignId' => $campaignId,
            'leadId' => $leadId,
            'phoneNumber' => $phone ?: null,
            'status' => 'Queued',
            'errorMessage' => $lead->get('whatsappOptIn') ? null : 'WhatsApp opt-in is not enabled.',
            'omniGoCRMWorkspaceId' => $campaign->get('omniGoCRMWorkspaceId'),
        ]);

        if (!$lead->get('whatsappOptIn')) {
            $recipient->set('status', 'Skipped');
        }

        $this->entityManager->saveEntity($recipient);

        return ResponseComposer::json([
            'accepted' => true,
            'recipientId' => $recipient->getId(),
            'status' => $recipient->get('status'),
            'leadId' => $leadId,
        ]);
    }
}

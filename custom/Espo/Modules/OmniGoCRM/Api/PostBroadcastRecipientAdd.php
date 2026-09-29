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

class PostBroadcastRecipientAdd implements Action
{
    public function __construct(
        private EntityManager $entityManager,
        private WorkspaceService $workspaceService,
        private WorkspaceMemberService $memberService,
    ) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();

        if (!isset($data->campaignId) || !is_string($data->campaignId)) {
            throw new BadRequest('campaignId is required.');
        }

        $campaignId = trim($data->campaignId);
        $campaign = $this->entityManager->getEntityById('BroadcastCampaign', $campaignId);

        if (!$campaign) {
            throw new BadRequest('Broadcast campaign not found.');
        }

        $workspaceId = $this->workspaceService->currentId();

        if (!$workspaceId) {
            throw new Forbidden('Select an active workspace before editing broadcasts.');
        }

        $this->memberService->activeMembership($workspaceId);

        if ((string) $campaign->get('omniGoCRMWorkspaceId') !== $workspaceId) {
            throw new Forbidden('The broadcast campaign is not available in the active workspace.');
        }

        if ($campaign->get('status') !== 'Draft') {
            throw new BadRequest('Recipients can only be changed while the campaign is a Draft.');
        }

        $leadId = isset($data->leadId) && is_string($data->leadId) ? trim($data->leadId) : '';

        if ($leadId === '') {
            throw new BadRequest('leadId is required.');
        }

        $lead = $this->entityManager->getEntityById('Lead', $leadId);

        if (!$lead) {
            throw new BadRequest('Lead not found.');
        }

        if ((string) $lead->get('omniGoCRMWorkspaceId') !== $workspaceId) {
            throw new Forbidden('The lead is not available in the active workspace.');
        }

        $variables = [];
        if (property_exists($data, 'variables')) {
            if (!is_object($data->variables)) {
                throw new BadRequest('variables must be an object of template placeholder values.');
            }

            if (count(get_object_vars($data->variables)) > 20) {
                throw new BadRequest('A recipient can have at most 20 template variables.');
            }

            foreach (get_object_vars($data->variables) as $key => $value) {
                if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,63}$/', (string) $key)) {
                    throw new BadRequest('Template variable names must start with a letter and contain only letters, numbers, or underscores.');
                }

                if (!is_string($value) && !is_int($value) && !is_float($value) && !is_bool($value)) {
                    throw new BadRequest('Template variable values must be strings or scalar values.');
                }

                $value = (string) $value;
                if (mb_strlen($value) > 1024) {
                    throw new BadRequest('Template variable values cannot exceed 1024 characters.');
                }

                $variables[$key] = $value;
            }
        }

        $recipient = $this->entityManager
            ->getRDBRepository('BroadcastRecipient')
            ->where([
                'campaignId' => $campaignId,
                'leadId' => $leadId,
                'omniGoCRMWorkspaceId' => $workspaceId,
                'deleted' => false,
            ])
            ->findOne();

        if (!$recipient) {
            $recipient = $this->entityManager->getNewEntity('BroadcastRecipient');
        }

        $phone = trim((string) $lead->get('whatsappNumber'));

        $recipient->setMultiple([
            'name' => trim((string) $lead->get('name')),
            'campaignId' => $campaignId,
            'leadId' => $leadId,
            'phoneNumber' => $phone ?: null,
            'status' => 'Queued',
            'errorMessage' => $lead->get('whatsappOptIn') ? null : 'WhatsApp opt-in is not enabled.',
            'variablesJson' => json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'omniGoCRMWorkspaceId' => $workspaceId,
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

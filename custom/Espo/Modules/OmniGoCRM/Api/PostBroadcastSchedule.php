<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;

class PostBroadcastSchedule implements Action
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

        $campaign = $this->entityManager->getEntityById(
            'BroadcastCampaign',
            trim($data->campaignId)
        );

        if (!$campaign) {
            throw new BadRequest('Broadcast campaign not found.');
        }

        if (!in_array($campaign->get('status'), ['Draft', 'Scheduled'], true)) {
            throw new BadRequest('Campaign is not schedulable from its current status.');
        }

        $templateName = trim((string) $campaign->get('templateName'));

        $template = $this->entityManager
            ->getRDBRepository('WhatsAppTemplate')
            ->where([
                'name' => $templateName,
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

        $campaign->set([
            'scheduledAt' => gmdate('Y-m-d H:i:s', strtotime($scheduledAt)),
            'status' => 'Scheduled',
            'languageCode' => $campaign->get('languageCode') ?: $template->get('languageCode'),
        ]);

        $this->entityManager->saveEntity($campaign);

        $count = $this->entityManager
            ->getRDBRepository('BroadcastRecipient')
            ->where([
                'campaignId' => $campaign->getId(),
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

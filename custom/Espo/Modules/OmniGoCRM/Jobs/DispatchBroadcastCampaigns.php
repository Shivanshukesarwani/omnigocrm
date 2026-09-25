<?php

namespace Espo\Modules\OmniGoCRM\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\Core\ORM\EntityManager;
use Espo\Modules\OmniGoCRM\Services\WhatsAppCloudApi;
use Espo\Modules\OmniGoCRM\Services\WhatsAppConversationService;
use Throwable;

class DispatchBroadcastCampaigns implements JobDataLess
{
    private const CAMPAIGN_LIMIT = 10;
    private const RECIPIENT_LIMIT = 100;

    public function __construct(
        private EntityManager $entityManager,
        private WhatsAppCloudApi $whatsApp,
        private WhatsAppConversationService $conversationService,
    ) {}

    public function run(): void
    {
        $now = gmdate('Y-m-d H:i:s');

        $campaigns = $this->entityManager
            ->getRDBRepository('BroadcastCampaign')
            ->where([
                'status' => ['Scheduled', 'Running'],
                'scheduledAt<=' => $now,
                'deleted' => false,
            ])
            ->order('scheduledAt', 'ASC')
            ->limit(self::CAMPAIGN_LIMIT)
            ->find();

        foreach ($campaigns as $campaign) {
            $campaign->set([
                'status' => 'Running',
                'startedAt' => $campaign->get('startedAt') ?: $now,
            ]);
            $this->entityManager->saveEntity($campaign);

            $this->dispatchCampaign($campaign);
        }
    }

    private function dispatchCampaign($campaign): void
    {
        $workspaceId = (string) $campaign->get('omniGoCRMWorkspaceId');

        $recipients = $this->entityManager
            ->getRDBRepository('BroadcastRecipient')
            ->where([
                'campaignId' => $campaign->getId(),
                'status' => 'Queued',
                'deleted' => false,
            ])
            ->limit(self::RECIPIENT_LIMIT)
            ->find();

        if (count($recipients) === 0) {
            $campaign->set([
                'status' => 'Completed',
                'completedAt' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->entityManager->saveEntity($campaign);
            return;
        }

        $templateName = trim((string) $campaign->get('templateName'));
        $languageCode = trim((string) $campaign->get('languageCode')) ?: 'en_US';
        $components = json_decode((string) ($campaign->get('componentsJson') ?: '[]'), true);

        if (!is_array($components)) {
            $components = [];
        }

        foreach ($recipients as $recipient) {
            $this->dispatchRecipient(
                $campaign,
                $recipient,
                $templateName,
                $languageCode,
                $components,
                $workspaceId,
            );
        }

        $remaining = $this->entityManager
            ->getRDBRepository('BroadcastRecipient')
            ->where([
                'campaignId' => $campaign->getId(),
                'status' => 'Queued',
                'deleted' => false,
            ])
            ->count();

        if ($remaining === 0) {
            $campaign->set([
                'status' => 'Completed',
                'completedAt' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->entityManager->saveEntity($campaign);
        }
    }

    private function dispatchRecipient(
        $campaign,
        $recipient,
        string $templateName,
        string $languageCode,
        array $components,
        string $workspaceId,
    ): void {
        $lead = null;
        $leadId = trim((string) $recipient->get('leadId'));

        if ($leadId !== '') {
            $lead = $this->entityManager->getEntityById('Lead', $leadId);
        }

        if (!$lead || !$lead->get('whatsappOptIn')) {
            $recipient->set([
                'status' => 'Skipped',
                'errorMessage' => 'Lead is missing or WhatsApp opt-in is not enabled.',
            ]);
            $this->entityManager->saveEntity($recipient);
            $this->incrementCampaign($campaign, false, false);
            return;
        }

        $phone = trim((string) $lead->get('whatsappNumber'));

        if ($phone === '') {
            $phone = trim((string) $recipient->get('phoneNumber'));
        }

        if ($phone === '') {
            $recipient->set([
                'status' => 'Skipped',
                'errorMessage' => 'No WhatsApp number is available.',
            ]);
            $this->entityManager->saveEntity($recipient);
            $this->incrementCampaign($campaign, false, false);
            return;
        }

        try {
            $result = $this->whatsApp->sendTemplate(
                recipient: $phone,
                templateName: $templateName,
                languageCode: $languageCode,
                components: $components,
            );

            $sentAt = gmdate('Y-m-d H:i:s');

            $message = $this->entityManager->getNewEntity('WhatsAppMessage');

            $message->set([
                'name' => $result->providerMessageId,
                'providerMessageId' => $result->providerMessageId,
                'direction' => 'Outbound',
                'status' => 'Sent',
                'messageType' => 'Template',
                'toNumber' => $phone,
                'templateName' => $templateName,
                'leadId' => $lead->getId(),
                'externalLeadId' => $lead->get('externalLeadId'),
                'sentAt' => $sentAt,
                'rawPayload' => json_encode($result->response, JSON_UNESCAPED_SLASHES),
                'omniGoCRMWorkspaceId' => $workspaceId,
            ]);

            $this->entityManager->saveEntity($message);

            $conversation = $this->conversationService->findOrCreate(
                waId: preg_replace('/\D+/', '', $phone) ?? $phone,
                lead: $lead,
            );

            $message->set('conversationId', $conversation->getId());
            $this->entityManager->saveEntity($message);
            $this->conversationService->outgoing(
                $conversation,
                '[Template] ' . $templateName,
                $sentAt,
            );

            $recipient->set([
                'status' => 'Sent',
                'providerMessageId' => $result->providerMessageId,
                'sentAt' => $sentAt,
                'errorMessage' => null,
            ]);
            $this->entityManager->saveEntity($recipient);

            $this->incrementCampaign($campaign, true, false);
        } catch (Throwable $e) {
            $recipient->set([
                'status' => 'Failed',
                'errorMessage' => mb_substr($e->getMessage(), 0, 2000),
            ]);
            $this->entityManager->saveEntity($recipient);
            $this->incrementCampaign($campaign, false, true);
        }
    }

    private function incrementCampaign($campaign, bool $sent, bool $failed): void
    {
        if ($sent) {
            $campaign->set('sentCount', (int) $campaign->get('sentCount') + 1);
        }

        if ($failed) {
            $campaign->set('failedCount', (int) $campaign->get('failedCount') + 1);
        }

        $this->entityManager->saveEntity($campaign);
    }
}

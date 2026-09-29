<?php

namespace Espo\Modules\OmniGoCRM\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\Core\ORM\EntityManager;
use Espo\Modules\OmniGoCRM\Services\WhatsAppCloudApi;
use Espo\Modules\OmniGoCRM\Services\WhatsAppConversationService;
use Espo\Modules\OmniGoCRM\Services\BillingService;
use Throwable;

class DispatchBroadcastCampaigns implements JobDataLess
{
    private const CAMPAIGN_LIMIT = 10;
    private const RECIPIENT_LIMIT = 100;

    public function __construct(
        private EntityManager $entityManager,
        private WhatsAppCloudApi $whatsApp,
        private WhatsAppConversationService $conversationService,
        private BillingService $billingService,
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

        $remainingQuotaByWorkspace = [];

        foreach ($campaigns as $campaign) {
            $workspaceId = trim((string) $campaign->get('omniGoCRMWorkspaceId'));

            if ($workspaceId === '') {
                $campaign->set('status', 'Canceled');
                $this->entityManager->saveEntity($campaign);
                continue;
            }

            if (!array_key_exists($workspaceId, $remainingQuotaByWorkspace)) {
                $remainingQuotaByWorkspace[$workspaceId] = $this->billingService
                    ->broadcastRecipientsRemaining($workspaceId);
            }

            $campaign->setMultiple([
                'status' => 'Running',
                'startedAt' => $campaign->get('startedAt') ?: $now,
            ]);
            $this->entityManager->saveEntity($campaign);

            $this->dispatchCampaign($campaign, $remainingQuotaByWorkspace[$workspaceId]);
        }
    }

    private function dispatchCampaign($campaign, ?int &$remainingQuota): void
    {
        $workspaceId = (string) $campaign->get('omniGoCRMWorkspaceId');

        $template = $this->entityManager->getRDBRepository('WhatsAppTemplate')->where([
            'name' => trim((string) $campaign->get('templateName')),
            'omniGoCRMWorkspaceId' => $workspaceId,
            'status' => 'Approved',
            'active' => true,
            'deleted' => false,
        ])->findOne();

        if (!$template) {
            $campaign->set('status', 'Canceled');
            $this->entityManager->saveEntity($campaign);
            $this->skipQueuedRecipients($campaign, $workspaceId, 'The campaign template is no longer active and approved.');
            return;
        }

        $recipients = $this->entityManager
            ->getRDBRepository('BroadcastRecipient')
            ->where([
                'campaignId' => $campaign->getId(),
                'omniGoCRMWorkspaceId' => $workspaceId,
                'status' => 'Queued',
                'deleted' => false,
            ])
            ->limit(self::RECIPIENT_LIMIT)
            ->find();

        if (count($recipients) === 0) {
            $campaign->setMultiple([
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
            if ($remainingQuota === 0) {
                $recipient->setMultiple([
                    'status' => 'Skipped',
                    'errorMessage' => 'The active plan monthly WhatsApp recipient limit has been reached.',
                ]);
                $this->entityManager->saveEntity($recipient);
                continue;
            }

            $sent = $this->dispatchRecipient(
                $campaign,
                $recipient,
                $templateName,
                $languageCode,
                $components,
                $workspaceId,
            );

            if ($sent && $remainingQuota !== null) $remainingQuota--;
        }

        $remaining = $this->entityManager
            ->getRDBRepository('BroadcastRecipient')
            ->where([
                'campaignId' => $campaign->getId(),
                'omniGoCRMWorkspaceId' => $workspaceId,
                'status' => 'Queued',
                'deleted' => false,
            ])
            ->count();

        if ($remaining === 0) {
            $campaign->setMultiple([
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
    ): bool {
        $lead = null;
        $leadId = trim((string) $recipient->get('leadId'));

        if ($leadId !== '') {
            $lead = $this->entityManager->getEntityById('Lead', $leadId);
        }

        $leadWorkspaceId = $lead ? trim((string) ($lead->get('omniGoCRMWorkspaceId') ?? '')) : '';

        if (!$lead || $workspaceId === '' || $leadWorkspaceId !== $workspaceId) {
            $recipient->setMultiple([
                'status' => 'Skipped',
                'errorMessage' => 'Lead does not belong to the campaign workspace.',
            ]);
            $this->entityManager->saveEntity($recipient);
            $this->incrementCampaign($campaign, false, false);
            return false;
        }

        if (!$lead->get('whatsappOptIn')) {
            $recipient->setMultiple([
                'status' => 'Skipped',
                'errorMessage' => 'Lead is missing or WhatsApp opt-in is not enabled.',
            ]);
            $this->entityManager->saveEntity($recipient);
            $this->incrementCampaign($campaign, false, false);
            return false;
        }

        $phone = trim((string) $lead->get('whatsappNumber'));

        if ($phone === '') {
            $phone = trim((string) $recipient->get('phoneNumber'));
        }

        if ($phone === '') {
            $recipient->setMultiple([
                'status' => 'Skipped',
                'errorMessage' => 'No WhatsApp number is available.',
            ]);
            $this->entityManager->saveEntity($recipient);
            $this->incrementCampaign($campaign, false, false);
            return false;
        }

        try {
            $variables = json_decode((string) $recipient->get('variablesJson'), true);
            if (!is_array($variables)) $variables = [];
            $components = $this->personalizeValue($components, $lead, $variables);
            $result = $this->whatsApp->sendTemplate(
                recipient: $phone,
                templateName: $templateName,
                languageCode: $languageCode,
                components: $components,
            );

            $sentAt = gmdate('Y-m-d H:i:s');

            $message = $this->entityManager->getNewEntity('WhatsAppMessage');

            $message->setMultiple([
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
                workspaceId: $workspaceId,
            );

            $message->set('conversationId', $conversation->getId());
            $this->entityManager->saveEntity($message);
            $this->conversationService->outgoing(
                $conversation,
                '[Template] ' . $templateName,
                $sentAt,
                countsAsReply: false,
            );

            $recipient->setMultiple([
                'status' => 'Sent',
                'providerMessageId' => $result->providerMessageId,
                'sentAt' => $sentAt,
                'errorMessage' => null,
            ]);
            $this->entityManager->saveEntity($recipient);

            $this->incrementCampaign($campaign, true, false);
            return true;
        } catch (Throwable $e) {
            $recipient->setMultiple([
                'status' => 'Failed',
                'errorMessage' => mb_substr($e->getMessage(), 0, 2000),
            ]);
            $this->entityManager->saveEntity($recipient);
            $this->incrementCampaign($campaign, false, true);
            return false;
        }
    }

    private function skipQueuedRecipients($campaign, string $workspaceId, string $reason): void
    {
        $recipients = $this->entityManager->getRDBRepository('BroadcastRecipient')->where([
            'campaignId' => $campaign->getId(),
            'omniGoCRMWorkspaceId' => $workspaceId,
            'status' => 'Queued',
            'deleted' => false,
        ])->find();

        foreach ($recipients as $recipient) {
            $recipient->setMultiple(['status' => 'Skipped', 'errorMessage' => $reason]);
            $this->entityManager->saveEntity($recipient);
        }
    }

    private function personalizeValue(mixed $value, $lead, array $variables = []): mixed
    {
        if (is_string($value)) {
            $values = [
                '{{firstName}}' => (string) $lead->get('firstName'),
                '{{lastName}}' => (string) $lead->get('lastName'),
                '{{whatsappNumber}}' => (string) $lead->get('whatsappNumber'),
                '{{name}}' => trim((string) $lead->get('firstName') . ' ' . (string) $lead->get('lastName')),
            ];

            foreach ($variables as $key => $variable) {
                if (is_string($key) && (is_string($variable) || is_numeric($variable))) {
                    $values['{{' . $key . '}}'] = (string) $variable;
                }
            }

            return strtr($value, $values);
        }

        if (!is_array($value)) return $value;

        foreach ($value as $key => $item) {
            $value[$key] = $this->personalizeValue($item, $lead, $variables);
        }

        return $value;
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

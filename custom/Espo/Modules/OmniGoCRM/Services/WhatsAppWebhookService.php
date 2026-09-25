<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Modules\Crm\Entities\Lead;
use stdClass;

class WhatsAppWebhookService
{
    public function __construct(
        private Config $config,
        private EntityManager $entityManager,
        private WhatsAppConversationService $conversationService,
    ) {}

    public function verify(string $mode, string $token, string $challenge): string
    {
        $expected = trim((string) $this->config->get('omniGoCRMWhatsAppVerifyToken'));

        if ($expected === '' || $mode !== 'subscribe' || !hash_equals($expected, $token)) {
            throw new Forbidden('WhatsApp webhook verification failed.');
        }

        return $challenge;
    }

    public function verifySignature(string $body, ?string $signature): void
    {
        $secret = trim((string) $this->config->get('omniGoCRMWhatsAppAppSecret'));

        if ($secret === '') {
            throw new Forbidden('WhatsApp webhook app secret is not configured.');
        }

        if (!is_string($signature) || !str_starts_with($signature, 'sha256=')) {
            throw new Forbidden('Missing WhatsApp webhook signature.');
        }

        $expected = 'sha256=' . hash_hmac('sha256', $body, $secret);

        if (!hash_equals($expected, $signature)) {
            throw new Forbidden('Invalid WhatsApp webhook signature.');
        }
    }

    public function handle(stdClass $payload): int
    {
        $created = 0;

        foreach (($payload->entry ?? []) as $entry) {
            if (!is_object($entry)) {
                continue;
            }

            foreach (($entry->changes ?? []) as $change) {
                if (!is_object($change)) {
                    continue;
                }

                $value = $change->value ?? null;

                if (!is_object($value)) {
                    continue;
                }

                $contacts = $this->indexContacts($value->contacts ?? []);
                $messages = $value->messages ?? [];

                foreach ($messages as $message) {
                    if (!is_object($message)) {
                        continue;
                    }

                    if ($this->storeMessage($message, $contacts, $value)) {
                        $created++;
                    }
                }

                foreach (($value->statuses ?? []) as $status) {
                    if (!is_object($status)) {
                        continue;
                    }

                    $this->updateMessageStatus($status);
                }
            }
        }

        return $created;
    }

    /**
     * @return array<string, string>
     */
    private function indexContacts(mixed $contacts): array
    {
        $result = [];

        if (!is_array($contacts)) {
            return $result;
        }

        foreach ($contacts as $contact) {
            if (!is_object($contact)) {
                continue;
            }

            $waId = isset($contact->wa_id) && is_string($contact->wa_id)
                ? $contact->wa_id
                : '';

            $name = '';

            if (isset($contact->profile) && is_object($contact->profile)) {
                $name = isset($contact->profile->name) && is_string($contact->profile->name)
                    ? trim($contact->profile->name)
                    : '';
            }

            if ($waId !== '') {
                $result[$waId] = $name;
            }
        }

        return $result;
    }

    private function storeMessage(stdClass $message, array $contacts, stdClass $value): bool
    {
        $providerMessageId = isset($message->id) && is_string($message->id)
            ? trim($message->id)
            : '';

        $from = isset($message->from) && is_string($message->from)
            ? trim($message->from)
            : '';

        if ($providerMessageId === '' || $from === '') {
            return false;
        }

        $existing = $this->entityManager
            ->getRDBRepository('WhatsAppMessage')
            ->where([
                'providerMessageId' => $providerMessageId,
                'deleted' => false,
            ])
            ->findOne();

        if ($existing) {
            return false;
        }

        $type = isset($message->type) && is_string($message->type)
            ? $message->type
            : 'unknown';

        $textBody = null;

        if ($type === 'text' && isset($message->text) && is_object($message->text)) {
            $textBody = isset($message->text->body) && is_string($message->text->body)
                ? $message->text->body
                : null;
        }

        $lead = $this->findLead($from);

        if (!$lead) {
            $lead = $this->createLead($from, $contacts[$from] ?? null);
        }

        $conversation = $this->conversationService->findOrCreate(
            waId: $from,
            lead: $lead,
            displayName: $contacts[$from] ?? null,
        );

        $messageEntity = $this->entityManager->getNewEntity('WhatsAppMessage');

        $messageEntity->set([
            'name' => $providerMessageId,
            'providerMessageId' => $providerMessageId,
            'direction' => 'Inbound',
            'status' => 'Received',
            'messageType' => $this->mapMessageType($type),
            'fromNumber' => $from,
            'toNumber' => $this->getBusinessNumber($value),
            'textBody' => $textBody,
            'leadId' => $lead?->getId(),
            'externalLeadId' => $lead?->get('externalLeadId'),
            'conversationId' => $conversation->getId(),
            'receivedAt' => $this->getMessageDateTime($message->timestamp ?? null),
            'rawPayload' => json_encode($message, JSON_UNESCAPED_SLASHES),
        ]);

        $this->entityManager->saveEntity($messageEntity);

        $preview = $textBody ?: ucfirst($type) . ' message';
        $this->conversationService->incoming(
            $conversation,
            $preview,
            $messageEntity->get('receivedAt'),
        );

        return true;
    }

    private function findLead(string $from): ?Lead
    {
        $digits = preg_replace('/\D+/', '', $from) ?? '';

        if ($digits === '') {
            return null;
        }

        /** @var ?Lead $lead */
        $lead = $this->entityManager
            ->getRDBRepositoryByClass(Lead::class)
            ->where([
                'OR' => [
                    ['whatsappNumber' => $from],
                    ['whatsappNumber*' => '%' . $digits . '%'],
                    ['phoneNumber*' => '%' . $digits . '%'],
                ],
                'deleted' => false,
            ])
            ->findOne();

        return $lead;
    }

    private function createLead(string $from, ?string $profileName): Lead
    {
        $lead = $this->entityManager
            ->getRDBRepositoryByClass(Lead::class)
            ->getNew();

        $lead->set([
            'whatsappNumber' => $from,
            'leadStage' => 'New',
            'source' => 'Other',
            'leadSourceDetail' => 'WhatsApp inbound',
        ]);

        if ($profileName !== null && $profileName !== '') {
            $parts = preg_split('/\s+/', trim($profileName)) ?: [];
            $lastName = array_pop($parts) ?: '';
            $firstName = implode(' ', $parts);

            if ($firstName !== '') {
                $lead->set('firstName', $firstName);
            }

            if ($lastName !== '') {
                $lead->set('lastName', $lastName);
            }
        }

        $this->entityManager->saveEntity($lead);

        return $lead;
    }

    private function updateMessageStatus(stdClass $status): void
    {
        $providerMessageId = isset($status->id) && is_string($status->id)
            ? trim($status->id)
            : '';

        $statusValue = isset($status->status) && is_string($status->status)
            ? $status->status
            : '';

        if ($providerMessageId === '' || $statusValue === '') {
            return;
        }

        $entity = $this->entityManager
            ->getRDBRepository('WhatsAppMessage')
            ->where([
                'providerMessageId' => $providerMessageId,
                'deleted' => false,
            ])
            ->findOne();

        if (!$entity) {
            return;
        }

        $mapped = match ($statusValue) {
            'sent' => 'Sent',
            'delivered' => 'Delivered',
            'read' => 'Read',
            'failed' => 'Failed',
            default => null,
        };

        if ($mapped === null) {
            return;
        }

        $entity->set('status', $mapped);

        if ($mapped === 'Failed' && isset($status->errors) && is_array($status->errors)) {
            $firstError = $status->errors[0] ?? null;

            if (is_object($firstError)) {
                if (isset($firstError->code)) {
                    $entity->set('errorCode', (string) $firstError->code);
                }

                if (isset($firstError->message)) {
                    $entity->set('errorMessage', (string) $firstError->message);
                }
            }
        }

        $this->entityManager->saveEntity($entity);
    }

    private function getBusinessNumber(stdClass $value): ?string
    {
        if (!isset($value->metadata) || !is_object($value->metadata)) {
            return null;
        }

        if (isset($value->metadata->display_phone_number) && is_string($value->metadata->display_phone_number)) {
            return $value->metadata->display_phone_number;
        }

        return null;
    }

    private function getMessageDateTime(mixed $timestamp): ?string
    {
        if (!is_string($timestamp) || !ctype_digit($timestamp)) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', (int) $timestamp);
    }

    private function mapMessageType(string $type): string
    {
        return match ($type) {
            'text' => 'Text',
            'image' => 'Image',
            'document' => 'Document',
            'audio' => 'Audio',
            'video' => 'Video',
            'interactive' => 'Interactive',
            'template' => 'Template',
            default => 'Unknown',
        };
    }
}

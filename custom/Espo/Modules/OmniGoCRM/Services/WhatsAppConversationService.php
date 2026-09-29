<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\ORM\EntityManager;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\Entity;

class WhatsAppConversationService
{
    public function __construct(private EntityManager $entityManager) {}

    public function findOrCreate(string $waId, ?Lead $lead = null, ?Contact $contact = null, ?string $displayName = null, ?string $workspaceId = null): Entity
    {
        $waId = trim($waId);
        $conversation = $this->entityManager->getRDBRepository('WhatsAppConversation')->where([
            'waId' => $waId,
            'omniGoCRMWorkspaceId' => $workspaceId,
            'deleted' => false,
        ])->findOne();

        if (!$conversation) {
            $conversation = $this->entityManager->getNewEntity('WhatsAppConversation');
            $conversation->setMultiple([
                'name' => $displayName ?: $waId,
                'waId' => $waId,
                'phoneNumber' => $waId,
                'status' => 'Open',
                'unreadCount' => 0,
                'omniGoCRMWorkspaceId' => $workspaceId,
            ]);
        }

        if ($displayName) {
            $conversation->set('customerDisplayName', $displayName);
            if (!$conversation->get('name')) {
                $conversation->set('name', $displayName);
            }
        }
        if ($lead) {
            $conversation->set('leadId', $lead->getId());
        }
        if ($contact) {
            $conversation->set('contactId', $contact->getId());
        }

        $this->entityManager->saveEntity($conversation);
        return $conversation;
    }

    public function incoming(Entity $conversation, string $preview, ?string $when = null): void
    {
        $when ??= gmdate('Y-m-d H:i:s');
        $conversation->setMultiple([
            'status' => 'Open',
            'unreadCount' => ((int) ($conversation->get('unreadCount') ?? 0)) + 1,
            'lastMessagePreview' => mb_substr($preview, 0, 1000),
            'lastMessageAt' => $when,
            'lastInboundAt' => $when,
            'lastTrackedInboundAt' => $when,
        ]);
        $this->entityManager->saveEntity($conversation);
    }

    public function outgoing(Entity $conversation, string $preview, ?string $when = null, bool $countsAsReply = true): void
    {
        $when ??= gmdate('Y-m-d H:i:s');
        $changes = [
            'lastMessagePreview' => mb_substr($preview, 0, 1000),
            'lastMessageAt' => $when,
            'lastOutboundAt' => $when,
        ];

        $inboundAt = $this->timestamp($conversation->get('lastInboundAt'));
        $lastReplyAt = $this->timestamp($conversation->get('lastResponseAt'));
        $outboundAt = $this->timestamp($when);

        if (
            $countsAsReply &&
            $inboundAt !== null &&
            ($lastReplyAt === null || $inboundAt > $lastReplyAt) &&
            $outboundAt !== null &&
            $outboundAt >= $inboundAt
        ) {
            $changes['lastResponseAt'] = $when;
            $changes['lastResponseSeconds'] = $outboundAt - $inboundAt;
        }

        $conversation->setMultiple($changes);
        $this->entityManager->saveEntity($conversation);
    }

    public function internalNote(Entity $conversation, string $note, ?string $when = null): void
    {
        $conversation->setMultiple([
            'lastMessagePreview' => mb_substr('[Internal note] ' . $note, 0, 1000),
            'lastMessageAt' => $when ?? gmdate('Y-m-d H:i:s'),
        ]);
        $this->entityManager->saveEntity($conversation);
    }

    public function markRead(Entity $conversation): void
    {
        $conversation->set('unreadCount', 0);
        $this->entityManager->saveEntity($conversation);
    }

    public function close(Entity $conversation): void
    {
        $conversation->setMultiple(['status' => 'Closed', 'unreadCount' => 0]);
        $this->entityManager->saveEntity($conversation);
    }

    private function timestamp(mixed $value): ?int
    {
        if (!is_string($value) || trim($value) === '') return null;

        try {
            return (new \DateTimeImmutable($value, new \DateTimeZone('UTC')))->getTimestamp();
        } catch (\Throwable) {
            return null;
        }
    }
}

<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\ORM\EntityManager;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\Entity;

class WhatsAppConversationService
{
    public function __construct(private EntityManager $entityManager) {}

    public function findOrCreate(string $waId, ?Lead $lead = null, ?Contact $contact = null, ?string $displayName = null): Entity
    {
        $waId = trim($waId);
        $conversation = $this->entityManager->getRDBRepository('WhatsAppConversation')->where([
            'waId' => $waId,
            'deleted' => false,
        ])->findOne();

        if (!$conversation) {
            $conversation = $this->entityManager->getNewEntity('WhatsAppConversation');
            $conversation->set([
                'name' => $displayName ?: $waId,
                'waId' => $waId,
                'phoneNumber' => $waId,
                'status' => 'Open',
                'unreadCount' => 0,
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
        $conversation->set([
            'status' => 'Open',
            'unreadCount' => ((int) ($conversation->get('unreadCount') ?? 0)) + 1,
            'lastMessagePreview' => mb_substr($preview, 0, 1000),
            'lastMessageAt' => $when,
            'lastInboundAt' => $when,
        ]);
        $this->entityManager->saveEntity($conversation);
    }

    public function outgoing(Entity $conversation, string $preview, ?string $when = null): void
    {
        $when ??= gmdate('Y-m-d H:i:s');
        $conversation->set([
            'lastMessagePreview' => mb_substr($preview, 0, 1000),
            'lastMessageAt' => $when,
            'lastOutboundAt' => $when,
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
        $conversation->set(['status' => 'Closed', 'unreadCount' => 0]);
        $this->entityManager->saveEntity($conversation);
    }
}

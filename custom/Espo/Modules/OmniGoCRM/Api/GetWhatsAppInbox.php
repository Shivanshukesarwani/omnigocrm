<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\ORM\EntityManager;

class GetWhatsAppInbox implements Action
{
    public function __construct(private EntityManager $entityManager) {}

    public function process(Request $request): Response
    {
        $workspaceId = trim((string) $request->getQueryParam('workspaceId'));
        $where = ['deleted' => false, 'status' => 'Open'];
        if ($workspaceId !== '') $where['omniGoCRMWorkspaceId'] = $workspaceId;
        $rows = [];
        foreach ($this->entityManager->getRDBRepository('WhatsAppConversation')->where($where)->order('lastMessageAt', true)->find() as $conversation) {
            $rows[] = [
                'id' => $conversation->getId(),
                'name' => $conversation->get('name'),
                'waId' => $conversation->get('waId'),
                'status' => $conversation->get('status'),
                'unreadCount' => (int) ($conversation->get('unreadCount') ?? 0),
                'lastMessageAt' => $conversation->get('lastMessageAt'),
                'lastMessagePreview' => $conversation->get('lastMessagePreview'),
                'leadId' => $conversation->get('leadId'),
                'contactId' => $conversation->get('contactId'),
                'assignedUserId' => $conversation->get('assignedUserId'),
            ];
        }
        return ResponseComposer::json(['items' => $rows]);
    }
}

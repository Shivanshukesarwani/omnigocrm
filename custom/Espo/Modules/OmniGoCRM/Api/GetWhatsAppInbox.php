<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\EntityManager;
use Espo\Modules\OmniGoCRM\Services\WorkspaceMemberService;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;

class GetWhatsAppInbox implements Action
{
    public function __construct(
        private EntityManager $entityManager,
        private WorkspaceService $workspaceService,
        private WorkspaceMemberService $memberService,
    ) {}

    public function process(Request $request): Response
    {
        $workspaceId = $this->workspaceService->currentId();

        if (!$workspaceId) {
            throw new Forbidden('Select an active workspace to view the WhatsApp inbox.');
        }

        $this->memberService->activeMembership($workspaceId);

        $where = [
            'omniGoCRMWorkspaceId' => $workspaceId,
            'deleted' => false,
            'status' => 'Open',
        ];
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
                'assignedTeamId' => $conversation->get('assignedTeamId'),
            ];
        }
        return ResponseComposer::json(['items' => $rows]);
    }
}

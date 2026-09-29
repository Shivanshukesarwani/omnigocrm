<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\EntityManager;
use Espo\Modules\OmniGoCRM\Services\WhatsAppConversationService;
use Espo\Modules\OmniGoCRM\Services\WorkspaceMemberService;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;

class PostWhatsAppConversationAction implements Action
{
    public function __construct(
        private EntityManager $entityManager,
        private WhatsAppConversationService $service,
        private WorkspaceService $workspaceService,
        private WorkspaceMemberService $memberService,
    ) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();
        $conversationId = isset($data->conversationId) && is_string($data->conversationId)
            ? trim($data->conversationId)
            : '';

        $action = isset($data->action) && is_string($data->action)
            ? trim($data->action)
            : '';

        if ($conversationId === '') {
            throw new BadRequest('conversationId is required.');
        }

        if (!in_array($action, ['read', 'close', 'open', 'assign', 'unassign', 'note'], true)) {
            throw new BadRequest('action must be read, close, open, assign, unassign, or note.');
        }

        $conversation = $this->entityManager->getEntityById(
            'WhatsAppConversation',
            $conversationId
        );

        if (!$conversation) {
            throw new BadRequest('WhatsApp conversation not found.');
        }

        $workspaceId = $this->workspaceService->currentId();

        if (!$workspaceId) {
            throw new Forbidden('Select an active workspace before updating conversations.');
        }

        if (in_array($action, ['assign', 'unassign'], true)) {
            $this->memberService->assertCanManageConversations($workspaceId);
        } else {
            $this->memberService->assertCanWrite($workspaceId);
        }

        if ((string) $conversation->get('omniGoCRMWorkspaceId') !== $workspaceId) {
            throw new Forbidden('This WhatsApp conversation belongs to a different workspace.');
        }

        match ($action) {
            'read' => $this->service->markRead($conversation),
            'close' => $this->service->close($conversation),
            'open' => $this->reopen($conversation),
            'assign' => $this->assign($conversation, $data, $workspaceId),
            'unassign' => $this->unassign($conversation),
            'note' => $this->addInternalNote($conversation, $data, $workspaceId),
        };

        return ResponseComposer::json([
            'accepted' => true,
            'conversationId' => $conversationId,
            'action' => $action,
            'status' => $conversation->get('status'),
            'unreadCount' => (int) ($conversation->get('unreadCount') ?? 0),
        ]);
    }

    private function reopen($conversation): void
    {
        $conversation->set('status', 'Open');
        $this->entityManager->saveEntity($conversation);
    }

    private function assign($conversation, \stdClass $data, string $workspaceId): void
    {
        $userId = isset($data->assignedUserId) && is_string($data->assignedUserId)
            ? trim($data->assignedUserId)
            : '';

        if ($userId === '') {
            throw new BadRequest('assignedUserId is required for assign.');
        }

        $membership = $this->entityManager->getRDBRepository('WorkspaceMember')->where([
            'workspaceId' => $workspaceId,
            'userId' => $userId,
            'status' => 'Active',
            'deleted' => false,
        ])->findOne();

        if (!$membership) {
            throw new BadRequest('Assignee must be an active member of the workspace.');
        }

        $conversation->set('assignedUserId', $userId);
        $this->entityManager->saveEntity($conversation);
    }

    private function unassign($conversation): void
    {
        $conversation->set('assignedUserId', null);
        $this->entityManager->saveEntity($conversation);
    }

    private function addInternalNote($conversation, \stdClass $data, string $workspaceId): void
    {
        $note = isset($data->note) && is_string($data->note) ? trim($data->note) : '';
        if ($note === '' || mb_strlen($note) > 10000) {
            throw new BadRequest('note is required and must not exceed 10000 characters.');
        }

        $now = gmdate('Y-m-d H:i:s');
        $message = $this->entityManager->getNewEntity('WhatsAppMessage');
        $message->setMultiple([
            'name' => 'Internal note',
            'direction' => 'Internal',
            'status' => 'Internal',
            'messageType' => 'InternalNote',
            'textBody' => $note,
            'conversationId' => $conversation->getId(),
            'leadId' => $conversation->get('leadId'),
            'contactId' => $conversation->get('contactId'),
            'sentAt' => $now,
            'omniGoCRMWorkspaceId' => $workspaceId,
        ]);
        $this->entityManager->saveEntity($message);
        $this->service->internalNote($conversation, $note, $now);
    }
}

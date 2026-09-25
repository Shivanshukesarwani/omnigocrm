<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;
use Espo\Modules\OmniGoCRM\Services\WhatsAppConversationService;

class PostWhatsAppConversationAction implements Action
{
    public function __construct(
        private EntityManager $entityManager,
        private WhatsAppConversationService $service,
    ) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();

        if ($data === null) {
            throw new BadRequest('A JSON payload is required.');
        }

        $conversationId = isset($data->conversationId) && is_string($data->conversationId)
            ? trim($data->conversationId)
            : '';

        $action = isset($data->action) && is_string($data->action)
            ? trim($data->action)
            : '';

        if ($conversationId === '') {
            throw new BadRequest('conversationId is required.');
        }

        if (!in_array($action, ['read', 'close', 'open'], true)) {
            throw new BadRequest('action must be read, close, or open.');
        }

        $conversation = $this->entityManager->getEntityById(
            'WhatsAppConversation',
            $conversationId
        );

        if (!$conversation) {
            throw new BadRequest('WhatsApp conversation not found.');
        }

        match ($action) {
            'read' => $this->service->markRead($conversation),
            'close' => $this->service->close($conversation),
            'open' => $this->reopen($conversation),
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
}

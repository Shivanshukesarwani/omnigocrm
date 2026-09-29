<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\EntityManager;
use Espo\Modules\OmniGoCRM\Services\WorkspaceMemberService;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;

class GetWhatsAppConversationMessages implements Action
{
    public function __construct(
        private EntityManager $entityManager,
        private WorkspaceService $workspaceService,
        private WorkspaceMemberService $memberService,
    ) {}

    public function process(Request $request): Response
    {
        $conversationId = trim((string) $request->getQueryParam('conversationId'));
        $workspaceId = $this->workspaceService->currentId();

        if ($conversationId === '') {
            throw new BadRequest('conversationId is required.');
        }

        if (!$workspaceId) {
            throw new Forbidden('Select an active workspace to view WhatsApp messages.');
        }

        $this->memberService->activeMembership($workspaceId);

        $conversation = $this->entityManager->getEntityById('WhatsAppConversation', $conversationId);

        if (!$conversation || (string) $conversation->get('omniGoCRMWorkspaceId') !== $workspaceId) {
            throw new Forbidden('The WhatsApp conversation is not available in the active workspace.');
        }

        $messages = $this->entityManager->getRDBRepository('WhatsAppMessage')->where([
            'conversationId' => $conversationId,
            'omniGoCRMWorkspaceId' => $workspaceId,
            'deleted' => false,
        ])->order('createdAt', 'DESC')->limit(500)->find();

        $items = [];

        foreach ($messages as $message) {
            $items[] = [
                'id' => $message->getId(),
                'direction' => $message->get('direction'),
                'status' => $message->get('status'),
                'messageType' => $message->get('messageType'),
                'textBody' => $message->get('textBody'),
                'mediaCaption' => $message->get('mediaCaption'),
                'mediaMimeType' => $message->get('mediaMimeType'),
                'mediaId' => $message->get('mediaId'),
                'receivedAt' => $message->get('receivedAt'),
                'sentAt' => $message->get('sentAt'),
                'isInternal' => $message->get('direction') === 'Internal',
            ];
        }

        return ResponseComposer::json(['items' => array_reverse($items)]);
    }
}

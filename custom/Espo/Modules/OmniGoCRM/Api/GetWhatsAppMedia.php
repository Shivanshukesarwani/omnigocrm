<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\EntityManager;
use Espo\Modules\OmniGoCRM\Services\WhatsAppCloudApi;
use Espo\Modules\OmniGoCRM\Services\WorkspaceMemberService;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;

class GetWhatsAppMedia implements Action
{
    public function __construct(
        private EntityManager $entityManager,
        private WhatsAppCloudApi $whatsApp,
        private WorkspaceService $workspaceService,
        private WorkspaceMemberService $memberService,
    ) {}

    public function process(Request $request): Response
    {
        $messageId = trim((string) $request->getQueryParam('messageId'));
        $workspaceId = $this->workspaceService->currentId();

        if ($messageId === '') throw new BadRequest('messageId is required.');
        if (!$workspaceId) throw new Forbidden('Select an active workspace to download WhatsApp media.');
        $this->memberService->activeMembership($workspaceId);

        $message = $this->entityManager->getEntityById('WhatsAppMessage', $messageId);
        if (
            !$message ||
            (string) $message->get('omniGoCRMWorkspaceId') !== $workspaceId ||
            trim((string) $message->get('mediaId')) === ''
        ) {
            throw new Forbidden('WhatsApp media is not available in the active workspace.');
        }

        $media = $this->whatsApp->downloadMedia((string) $message->get('mediaId'));
        $inline = in_array($media['mimeType'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);

        return ResponseComposer::empty()
            ->setHeader('Content-Type', $media['mimeType'])
            ->setHeader('Content-Length', (string) $media['size'])
            ->setHeader('Content-Disposition', $inline ? 'inline' : 'attachment')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($media['body']);
    }
}

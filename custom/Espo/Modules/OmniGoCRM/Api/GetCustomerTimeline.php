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

class GetCustomerTimeline implements Action
{
    public function __construct(
        private EntityManager $entityManager,
        private WorkspaceService $workspaceService,
        private WorkspaceMemberService $memberService,
    ) {}

    public function process(Request $request): Response
    {
        $type = trim((string) $request->getQueryParam('type'));
        $id = trim((string) $request->getQueryParam('id'));
        if (!in_array($type, ['Lead', 'Contact', 'Account'], true) || $id === '') {
            throw new BadRequest('type must be Lead, Contact or Account and id is required.');
        }

        $record = $this->entityManager->getEntityById($type, $id);
        $workspaceId = $this->workspaceService->currentId();

        if (!$workspaceId) {
            throw new Forbidden('Select an active workspace to view the customer timeline.');
        }

        $this->memberService->activeMembership($workspaceId);

        if (!$record || (string) $record->get('omniGoCRMWorkspaceId') !== $workspaceId) {
            throw new Forbidden('The requested CRM record is not available in the active workspace.');
        }

        $items = [];
        foreach (['Task', 'Note', 'WhatsAppMessage', 'Opportunity'] as $entityType) {
            $repo = $this->entityManager->getRDBRepository($entityType);
            foreach ($repo->where(['omniGoCRMWorkspaceId' => $workspaceId, 'deleted' => false])->find() as $record) {
                $matches = false;
                foreach (['leadId', 'contactId', 'accountId', 'parentId'] as $linkField) {
                    if ((string) $record->get($linkField) === $id) { $matches = true; break; }
                }
                if (!$matches) continue;
                $items[] = [
                    'id' => $record->getId(),
                    'type' => $entityType,
                    'name' => $record->get('name'),
                    'status' => $record->get('status'),
                    'date' => $record->get('modifiedAt') ?: $record->get('createdAt'),
                    'preview' => $record->get('textBody') ?: $record->get('description') ?: $record->get('notes'),
                ];
            }
        }

        usort($items, fn($a, $b) => strcmp((string) $b['date'], (string) $a['date']));
        return ResponseComposer::json(['recordType' => $type, 'recordId' => $id, 'items' => array_slice($items, 0, 200)]);
    }
}

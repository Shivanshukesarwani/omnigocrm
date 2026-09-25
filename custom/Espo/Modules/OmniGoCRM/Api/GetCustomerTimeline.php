<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;

class GetCustomerTimeline implements Action
{
    public function __construct(private EntityManager $entityManager) {}

    public function process(Request $request): Response
    {
        $type = trim((string) $request->getQueryParam('type'));
        $id = trim((string) $request->getQueryParam('id'));
        if (!in_array($type, ['Lead', 'Contact', 'Account'], true) || $id === '') {
            throw new BadRequest('type must be Lead, Contact or Account and id is required.');
        }

        $workspaceId = trim((string) $this->entityManager->getEntityById($type, $id)?->get('omniGoCRMWorkspaceId'));
        if ($workspaceId === '') {
            throw new BadRequest('Record is not assigned to an OmniGoCRM workspace.');
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

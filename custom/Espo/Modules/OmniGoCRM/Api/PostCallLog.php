<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Modules\OmniGoCRM\Services\WorkspaceMemberService;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;

class PostCallLog implements Action
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private WorkspaceService $workspaceService,
        private WorkspaceMemberService $memberService,
    ) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();
        $workspaceId = $this->workspaceService->currentId();

        if (!$workspaceId) {
            throw new BadRequest('Select an active OmniGoCRM workspace.');
        }

        $this->memberService->activeMembership($workspaceId);
        $leadId = $this->stringValue($data, 'leadId');
        $contactId = $this->stringValue($data, 'contactId');
        $phone = preg_replace('/\D+/', '', $this->stringValue($data, 'phoneNumber')) ?? '';
        $direction = ucfirst(strtolower($this->stringValue($data, 'direction') ?: 'Outbound'));

        if (!in_array($direction, ['Inbound', 'Outbound'], true)) {
            throw new BadRequest('direction must be Inbound or Outbound.');
        }

        if ($phone === '') {
            throw new BadRequest('phoneNumber is required.');
        }

        $duration = max(0, (int) ($data->duration ?? 0));
        $startedAt = $this->dateValue($data, 'startedAt') ?: gmdate('Y-m-d H:i:s');
        $endedAt = gmdate('Y-m-d H:i:s', strtotime($startedAt) + $duration);

        $call = $this->entityManager->getNewEntity('Call');

        $call->setMultiple([
            'name' => ($direction === 'Inbound' ? 'Inbound' : 'Outbound') . ' mobile call ' . $phone,
            'status' => $this->mapStatus($this->stringValue($data, 'status')),
            'dateStart' => $startedAt,
            'dateEnd' => $endedAt,
            'direction' => $direction,
            'duration' => $duration,
            'assignedUserId' => $this->user->getId(),
            'omniGoCRMPhoneNumber' => $phone,
            'omniGoCRMProvider' => $this->stringValue($data, 'provider') ?: 'mobile',
            'omniGoCRMExternalCallId' => $this->stringValue($data, 'externalCallId') ?: null,
            'omniGoCRMWorkspaceId' => $workspaceId,
        ]);

        if ($leadId !== '') {
            $lead = $this->entityManager->getEntityById('Lead', $leadId);

            if (!$lead || (string) $lead->get('omniGoCRMWorkspaceId') !== $workspaceId) {
                throw new BadRequest('Lead not found in the active workspace.');
            }

            $call->setMultiple([
                'parentId' => $leadId,
                'parentType' => 'Lead',
            ]);
        } elseif ($contactId !== '') {
            $contact = $this->entityManager->getEntityById('Contact', $contactId);

            if (!$contact || (string) $contact->get('omniGoCRMWorkspaceId') !== $workspaceId) {
                throw new BadRequest('Contact not found in the active workspace.');
            }

            $call->setMultiple([
                'parentId' => $contactId,
                'parentType' => 'Contact',
            ]);
        }

        $this->entityManager->saveEntity($call);

        return ResponseComposer::json([
            'accepted' => true,
            'callId' => $call->getId(),
            'status' => $call->get('status'),
            'assignedUserId' => $this->user->getId(),
        ]);
    }

    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'answered', 'completed', 'connected', 'held' => 'Held',
            'missed', 'no-answer', 'busy', 'failed', 'rejected', 'declined', 'not held' => 'Not Held',
            default => 'Planned',
        };
    }

    private function stringValue(\stdClass $data, string $key): string
    {
        return isset($data->{$key}) && is_string($data->{$key}) ? trim($data->{$key}) : '';
    }

    private function dateValue(\stdClass $data, string $key): ?string
    {
        $value = $this->stringValue($data, $key);

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : gmdate('Y-m-d H:i:s', $timestamp);
    }
}

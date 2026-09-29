<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;
use DateTimeImmutable;
use Throwable;

class PostFollowUp implements Action
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private WorkspaceService $workspaceService,
    ) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();

        $name = is_string($data->name ?? null) ? trim($data->name) : '';
        $dateStart = is_string($data->dateStart ?? null) ? trim($data->dateStart) : '';
        $parentType = is_string($data->parentType ?? null) ? trim($data->parentType) : '';
        $parentId = is_string($data->parentId ?? null) ? trim($data->parentId) : '';
        $description = is_string($data->description ?? null) ? trim($data->description) : '';
        $priority = is_string($data->priority ?? null) ? trim($data->priority) : 'Normal';
        $assignedUserId = is_string($data->assignedUserId ?? null) ? trim($data->assignedUserId) : '';

        if ($name === '' || $dateStart === '' || $parentType === '' || $parentId === '') {
            throw new BadRequest('name, dateStart, parentType and parentId are required.');
        }

        if (!in_array($parentType, ['Lead', 'Contact', 'Account', 'Opportunity'], true)) {
            throw new BadRequest('parentType must be Lead, Contact, Account or Opportunity.');
        }

        try {
            $date = new DateTimeImmutable($dateStart);
            $normalizedDateStart = $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (Throwable) {
            throw new BadRequest('dateStart must be a valid ISO 8601 date/time value.');
        }

        $workspaceId = $this->workspaceService->currentId();

        if ($workspaceId === null && !$this->user->isSystem()) {
            throw new BadRequest('Select an active OmniGoCRM workspace first.');
        }

        $parent = $this->entityManager->getEntityById($parentType, $parentId);

        if (!$parent) {
            throw new BadRequest('Parent CRM record was not found.');
        }

        if (!$this->user->isSystem() && $workspaceId !== null) {
            $parentWorkspaceId = trim((string) ($parent->get('omniGoCRMWorkspaceId') ?: $parent->get('workspaceId')));

            if ($parentWorkspaceId !== $workspaceId) {
                throw new BadRequest('Parent CRM record is outside the active workspace.');
            }
        }

        if ($assignedUserId !== '') {
            $membership = $this->entityManager
                ->getRDBRepository('WorkspaceMember')
                ->where([
                    'workspaceId' => $workspaceId,
                    'userId' => $assignedUserId,
                    'status' => 'Active',
                    'deleted' => false,
                ])
                ->findOne();

            if (!$membership) {
                throw new BadRequest('assignedUserId must belong to an active workspace member.');
            }
        }

        $task = $this->entityManager->getNewEntity('Task');

        $task->setMultiple([
            'name' => $name,
            'status' => 'Not Started',
            'priority' => $priority !== '' ? $priority : 'Normal',
            'dateStart' => $normalizedDateStart,
            'description' => $description !== '' ? $description : null,
            'parentType' => $parentType,
            'parentId' => $parentId,
            'omniGoCRMIsFollowUp' => true,
        ]);

        if ($assignedUserId !== '') {
            $task->set('assignedUserId', $assignedUserId);
        }

        if ($workspaceId !== null) {
            $task->set('omniGoCRMWorkspaceId', $workspaceId);
        }

        $this->entityManager->saveEntity($task);

        return ResponseComposer::json([
            'accepted' => true,
            'id' => $task->getId(),
            'name' => $task->get('name'),
            'dateStart' => $task->get('dateStart'),
            'parentType' => $task->get('parentType'),
            'parentId' => $task->get('parentId'),
            'priority' => $task->get('priority'),
            'status' => $task->get('status'),
        ]);
    }
}

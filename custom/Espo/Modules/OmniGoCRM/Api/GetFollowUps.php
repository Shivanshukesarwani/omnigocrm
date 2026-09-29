<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Entities\User;
use Espo\Core\ORM\EntityManager;

class GetFollowUps implements Action
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
    ) {}

    public function process(Request $request): Response
    {
        $workspaceId = trim((string) $this->user->get('omniGoCRMCurrentWorkspaceId'));

        if ($workspaceId === '' && !$this->user->isSystem()) {
            return ResponseComposer::json(['list' => []]);
        }

        $where = [
            'omniGoCRMIsFollowUp' => true,
            'status!=' => 'Completed',
            'deleted' => false,
        ];

        if ($workspaceId !== '' && !$this->user->isSystem()) {
            $where['omniGoCRMWorkspaceId'] = $workspaceId;
        }

        $tasks = $this->entityManager
            ->getRDBRepository('Task')
            ->where($where)
            ->order('dateStart', 'ASC')
            ->limit(100)
            ->find();

        $list = [];

        foreach ($tasks as $task) {
            $list[] = [
                'id' => $task->getId(),
                'name' => $task->get('name'),
                'description' => $task->get('description'),
                'status' => $task->get('status'),
                'priority' => $task->get('priority'),
                'dateStart' => $task->get('dateStart'),
                'dateEnd' => $task->get('dateEnd'),
                'parentType' => $task->get('parentType'),
                'parentId' => $task->get('parentId'),
                'assignedUserId' => $task->get('assignedUserId'),
            ];
        }

        return ResponseComposer::json(['list' => $list]);
    }
}

<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\ORM\Entity;

class WorkspaceMemberService
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
    ) {}

    public function activeMembership(string $workspaceId): Entity
    {
        $membership = $this->entityManager
            ->getRDBRepository('WorkspaceMember')
            ->where([
                'workspaceId' => $workspaceId,
                'userId' => $this->user->getId(),
                'status' => 'Active',
                'deleted' => false,
            ])
            ->findOne();

        if (!$membership) {
            throw new Forbidden('You are not an active member of this workspace.');
        }

        return $membership;
    }

    public function canManage(string $workspaceId): bool
    {
        $membership = $this->activeMembership($workspaceId);
        return in_array($membership->get('role'), ['Owner', 'Admin'], true);
    }

    public function addMember(string $workspaceId, string $userId, string $role): Entity
    {
        $this->assertManager($workspaceId);

        if (!in_array($role, ['Admin', 'Manager', 'Agent', 'Viewer'], true)) {
            throw new \InvalidArgumentException('Invalid workspace role.');
        }

        $workspace = $this->entityManager->getEntityById('Workspace', $workspaceId);
        $user = $this->entityManager->getEntityById('User', $userId);

        if (!$workspace || !$user) {
            throw new \InvalidArgumentException('Workspace or user not found.');
        }

        $repo = $this->entityManager->getRDBRepository('WorkspaceMember');
        $membership = $repo->where([
            'workspaceId' => $workspaceId,
            'userId' => $userId,
            'deleted' => false,
        ])->findOne();

        if (!$membership) {
            $membership = $repo->getNew();
        }

        $membership->set([
            'name' => $workspace->get('name') . ' / ' . $user->get('name'),
            'workspaceId' => $workspaceId,
            'userId' => $userId,
            'role' => $role,
            'status' => 'Active',
            'joinedAt' => gmdate('Y-m-d H:i:s'),
        ]);

        $this->entityManager->saveEntity($membership);

        return $membership;
    }

    public function changeRole(string $workspaceId, string $membershipId, string $role): Entity
    {
        $this->assertManager($workspaceId);

        if (!in_array($role, ['Owner', 'Admin', 'Manager', 'Agent', 'Viewer'], true)) {
            throw new \InvalidArgumentException('Invalid workspace role.');
        }

        $membership = $this->entityManager->getEntityById('WorkspaceMember', $membershipId);

        if (!$membership || (string) $membership->get('workspaceId') !== $workspaceId) {
            throw new \InvalidArgumentException('Workspace membership not found.');
        }

        $membership->set('role', $role);
        $this->entityManager->saveEntity($membership);

        return $membership;
    }

    public function setStatus(string $workspaceId, string $membershipId, string $status): Entity
    {
        $this->assertManager($workspaceId);

        if (!in_array($status, ['Active', 'Suspended', 'Removed'], true)) {
            throw new \InvalidArgumentException('Invalid member status.');
        }

        $membership = $this->entityManager->getEntityById('WorkspaceMember', $membershipId);

        if (!$membership || (string) $membership->get('workspaceId') !== $workspaceId) {
            throw new \InvalidArgumentException('Workspace membership not found.');
        }

        if ((string) $membership->get('userId') === $this->user->getId() && $status !== 'Active') {
            throw new Forbidden('You cannot suspend or remove your own active membership.');
        }

        $membership->set('status', $status);
        $this->entityManager->saveEntity($membership);

        return $membership;
    }

    /**
     * @return list<Entity>
     */
    public function list(string $workspaceId): array
    {
        $this->activeMembership($workspaceId);

        return $this->entityManager
            ->getRDBRepository('WorkspaceMember')
            ->where([
                'workspaceId' => $workspaceId,
                'deleted' => false,
            ])
            ->order('createdAt', 'ASC')
            ->find();
    }

    private function assertManager(string $workspaceId): void
    {
        if (!$this->canManage($workspaceId)) {
            throw new Forbidden('Workspace admin access is required.');
        }
    }
}

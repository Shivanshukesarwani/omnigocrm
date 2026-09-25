<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\ORM\Entity;

class WorkspaceService
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
    ) {}

    public function create(string $name, ?string $slug = null): Entity
    {
        $name = trim($name);

        if ($name === '') {
            throw new BadRequest('Workspace name is required.');
        }

        $slug = $this->normalizeSlug($slug ?: $name);

        if ($slug === '') {
            throw new BadRequest('Workspace slug is required.');
        }

        $existing = $this->entityManager
            ->getRDBRepository('Workspace')
            ->where([
                'slug' => $slug,
                'deleted' => false,
            ])
            ->findOne();

        if ($existing) {
            throw new BadRequest('Workspace slug is already in use.');
        }

        $workspace = $this->entityManager->getNewEntity('Workspace');

        $workspace->set([
            'name' => $name,
            'slug' => $slug,
            'status' => 'Active',
            'plan' => 'Free',
            'subscriptionStatus' => 'Trialing',
            'trialEndsAt' => gmdate('Y-m-d H:i:s', time() + 14 * 86400),
            'ownerUserId' => $this->user->getId(),
        ]);

        $this->entityManager->saveEntity($workspace);

        $membership = $this->entityManager->getNewEntity('WorkspaceMember');

        $membership->set([
            'name' => $name . ' / ' . $this->user->get('name'),
            'workspaceId' => $workspace->getId(),
            'userId' => $this->user->getId(),
            'role' => 'Owner',
            'status' => 'Active',
            'joinedAt' => gmdate('Y-m-d H:i:s'),
        ]);

        $this->entityManager->saveEntity($membership);

        $this->switch($workspace->getId());

        return $workspace;
    }

    /**
     * @return list<Entity>
     */
    public function mine(): array
    {
        $memberships = $this->entityManager
            ->getRDBRepository('WorkspaceMember')
            ->where([
                'userId' => $this->user->getId(),
                'status' => 'Active',
                'deleted' => false,
            ])
            ->find();

        $result = [];

        foreach ($memberships as $membership) {
            $workspaceId = (string) $membership->get('workspaceId');

            if ($workspaceId === '') {
                continue;
            }

            $workspace = $this->entityManager->getEntityById('Workspace', $workspaceId);

            if ($workspace && $workspace->get('status') === 'Active') {
                $result[] = $workspace;
            }
        }

        return $result;
    }

    public function currentId(): ?string
    {
        $id = trim((string) $this->user->get('omniGoCRMCurrentWorkspaceId'));

        return $id !== '' ? $id : null;
    }

    public function switch(string $workspaceId): Entity
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
            throw new BadRequest('You are not an active member of this workspace.');
        }

        $workspace = $this->entityManager->getEntityById('Workspace', $workspaceId);

        if (!$workspace || $workspace->get('status') !== 'Active') {
            throw new BadRequest('Workspace is not active.');
        }

        $this->user->set('omniGoCRMCurrentWorkspaceId', $workspaceId);
        $this->entityManager->saveEntity($this->user);

        return $workspace;
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        return trim($slug, '-');
    }
}

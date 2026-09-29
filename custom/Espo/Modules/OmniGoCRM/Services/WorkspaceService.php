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

        $slug = $this->uniqueSlug($slug ?: $name);

        $workspace = $this->entityManager->getNewEntity('Workspace');

        $workspace->setMultiple([
            'name' => $name,
            'slug' => $slug,
            'status' => 'Active',
            'plan' => 'Free',
            'subscriptionStatus' => 'Trialing',
            'trialEndsAt' => gmdate('Y-m-d H:i:s', time() + 14 * 86400),
            'ownerUserId' => $this->user->getId(),
        ]);

        $this->entityManager->saveEntity($workspace);
        $this->activateOwnerWorkspace($workspace);

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

        usort($result, static function (Entity $left, Entity $right): int {
            $createdAtComparison = strcmp(
                (string) $left->get('createdAt'),
                (string) $right->get('createdAt')
            );

            return $createdAtComparison !== 0
                ? $createdAtComparison
                : strcmp($left->getId(), $right->getId());
        });

        return $result;
    }

    public function currentId(): ?string
    {
        if ($this->user->isSystem()) {
            return null;
        }

        $id = trim((string) $this->user->get('omniGoCRMCurrentWorkspaceId'));

        if ($id !== '' && $this->isActiveMember($id)) {
            return $id;
        }

        $workspaces = $this->mine();

        if ($workspaces === []) {
            $name = trim((string) $this->user->get('name'));
            $workspace = $this->create($name !== '' ? $name . "'s Workspace" : 'My Workspace');

            return $workspace->getId();
        }

        $id = $workspaces[0]->getId();
        $this->switch($id);

        return $id;
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

    public function activateOwnerWorkspace(Entity $workspace): void
    {
        if (
            $this->user->isSystem() ||
            (string) $workspace->get('ownerUserId') !== $this->user->getId() ||
            $workspace->get('status') !== 'Active'
        ) {
            return;
        }

        $membership = $this->entityManager
            ->getRDBRepository('WorkspaceMember')
            ->where([
                'workspaceId' => $workspace->getId(),
                'userId' => $this->user->getId(),
                'deleted' => false,
            ])
            ->findOne();

        if (!$membership) {
            $membership = $this->entityManager->getNewEntity('WorkspaceMember');

            $membership->setMultiple([
                'name' => $workspace->get('name') . ' / ' . $this->user->get('name'),
                'workspaceId' => $workspace->getId(),
                'userId' => $this->user->getId(),
                'role' => 'Owner',
                'status' => 'Active',
                'joinedAt' => gmdate('Y-m-d H:i:s'),
            ]);

            $this->entityManager->saveEntity($membership);
        }

        $this->switch($workspace->getId());
    }

    public function uniqueSlug(string $value): string
    {
        $base = $this->normalizeSlug($value);
        $base = $base !== '' ? $base : 'workspace';
        $base = substr($base, 0, 100);
        $slug = $base;
        $suffix = 2;
        $repository = $this->entityManager->getRDBRepository('Workspace');

        while ($repository->where([
            'slug' => $slug,
            'deleted' => false,
        ])->findOne()) {
            $ending = '-' . $suffix++;
            $slug = rtrim(substr($base, 0, 100 - strlen($ending)), '-') . $ending;
        }

        return $slug;
    }

    private function isActiveMember(string $workspaceId): bool
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
            return false;
        }

        $workspace = $this->entityManager->getEntityById('Workspace', $workspaceId);

        return $workspace !== null && $workspace->get('status') === 'Active';
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        return trim(substr($slug, 0, 100), '-');
    }
}

<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;

class BillingService
{
    private const PLANS = [
        'Free' => [
            'maxUsers' => 2,
            'maxLeads' => 1000,
            'maxBroadcastRecipientsPerMonth' => 250,
            'whatsapp' => true,
            'calling' => true,
            'sales' => true,
        ],
        'Starter' => [
            'maxUsers' => 5,
            'maxLeads' => 10000,
            'maxBroadcastRecipientsPerMonth' => 5000,
            'whatsapp' => true,
            'calling' => true,
            'sales' => true,
        ],
        'Business' => [
            'maxUsers' => 25,
            'maxLeads' => 100000,
            'maxBroadcastRecipientsPerMonth' => 50000,
            'whatsapp' => true,
            'calling' => true,
            'sales' => true,
        ],
        'Enterprise' => [
            'maxUsers' => null,
            'maxLeads' => null,
            'maxBroadcastRecipientsPerMonth' => null,
            'whatsapp' => true,
            'calling' => true,
            'sales' => true,
        ],
    ];

    public function __construct(
        private EntityManager $entityManager,
        private User $user,
    ) {}

    public function entitlements(?string $workspaceId = null): array
    {
        $workspaceId = $workspaceId ?: trim((string) $this->user->get('omniGoCRMCurrentWorkspaceId'));

        $workspace = $this->entityManager->getEntityById('Workspace', $workspaceId);

        if (!$workspace) {
            throw new Forbidden('Workspace not found.');
        }

        $plan = (string) ($workspace->get('plan') ?: 'Free');
        $status = (string) ($workspace->get('subscriptionStatus') ?: 'None');

        return [
            'workspaceId' => $workspaceId,
            'plan' => $plan,
            'subscriptionStatus' => $status,
            'limits' => self::PLANS[$plan] ?? self::PLANS['Free'],
        ];
    }

    public function can(string $feature, ?string $workspaceId = null): bool
    {
        $entitlements = $this->entitlements($workspaceId);
        return (bool) ($entitlements['limits'][$feature] ?? false);
    }

    public function setPlan(
        string $workspaceId,
        string $plan,
        string $status = 'Active',
    ): array {
        $this->assertManager($workspaceId);

        if (!isset(self::PLANS[$plan])) {
            throw new \InvalidArgumentException('Invalid plan.');
        }

        $workspace = $this->entityManager->getEntityById('Workspace', $workspaceId);

        if (!$workspace) {
            throw new \InvalidArgumentException('Workspace not found.');
        }

        $workspace->set([
            'plan' => $plan,
            'subscriptionStatus' => $status,
        ]);

        $this->entityManager->saveEntity($workspace);

        $subscription = $this->entityManager
            ->getRDBRepository('BillingSubscription')
            ->where([
                'workspaceId' => $workspaceId,
                'deleted' => false,
            ])
            ->findOne();

        if (!$subscription) {
            $subscription = $this->entityManager->getNewEntity('BillingSubscription');
        }

        $subscription->set([
            'name' => $workspace->get('name') . ' Subscription',
            'workspaceId' => $workspaceId,
            'provider' => 'Manual',
            'plan' => $plan,
            'status' => $status,
            'currency' => 'INR',
            'omniGoCRMWorkspaceId' => $workspaceId,
        ]);

        $this->entityManager->saveEntity($subscription);

        return $this->entitlements($workspaceId);
    }

    private function assertManager(string $workspaceId): void
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

        if (!$membership || !in_array($membership->get('role'), ['Owner', 'Admin'], true)) {
            throw new \RuntimeException('Workspace admin access is required.');
        }
    }
}

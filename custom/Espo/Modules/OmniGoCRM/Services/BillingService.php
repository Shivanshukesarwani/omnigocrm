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

    public function broadcastRecipientsRemaining(string $workspaceId): ?int
    {
        $limit = $this->entitlements($workspaceId)['limits']['maxBroadcastRecipientsPerMonth'];

        if ($limit === null) return null;

        $used = $this->entityManager->getRDBRepository('BroadcastRecipient')->where([
            'omniGoCRMWorkspaceId' => $workspaceId,
            'status' => 'Sent',
            'sentAt>=' => gmdate('Y-m-01 00:00:00'),
            'deleted' => false,
        ])->count();

        return max(0, (int) $limit - $used);
    }

    public function setPlan(
        string $workspaceId,
        string $plan,
        string $status = 'Active',
    ): array {
        $this->assertPlatformAdministrator();

        if (!isset(self::PLANS[$plan])) {
            throw new \InvalidArgumentException('Invalid plan.');
        }

        if (!in_array($status, ['Trialing', 'Active', 'Past Due', 'Canceled', 'None'], true)) {
            throw new \InvalidArgumentException('Invalid subscription status.');
        }

        $workspace = $this->entityManager->getEntityById('Workspace', $workspaceId);

        if (!$workspace) {
            throw new \InvalidArgumentException('Workspace not found.');
        }

        $workspace->setMultiple([
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

        $subscription->setMultiple([
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

    private function assertPlatformAdministrator(): void
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden('Only a platform administrator can manually change a subscription plan.');
        }
    }
}

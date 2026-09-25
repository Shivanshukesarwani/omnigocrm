<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;

class DashboardService
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
    ) {}

    public function summary(): array
    {
        $workspaceId = trim((string) $this->user->get('omniGoCRMCurrentWorkspaceId'));

        if ($workspaceId === '') {
            throw new BadRequest('Select an active OmniGoCRM workspace.');
        }

        $counts = [];

        foreach ([
            'Lead' => 'leads',
            'Contact' => 'contacts',
            'Account' => 'accounts',
            'Opportunity' => 'opportunities',
            'Task' => 'tasks',
            'Quote' => 'quotes',
            'Order' => 'orders',
            'Payment' => 'payments',
            'WhatsAppConversation' => 'whatsappConversations',
        ] as $entityType => $key) {
            $counts[$key] = $this->entityManager
                ->getRDBRepository($entityType)
                ->where([
                    'omniGoCRMWorkspaceId' => $workspaceId,
                    'deleted' => false,
                ])
                ->count();
        }

        $counts['openTasks'] = $this->entityManager
            ->getRDBRepository('Task')
            ->where([
                'omniGoCRMWorkspaceId' => $workspaceId,
                'status!=' => 'Completed',
                'deleted' => false,
            ])
            ->count();

        $counts['unreadWhatsApp'] = $this->entityManager
            ->getRDBRepository('WhatsAppConversation')
            ->where([
                'omniGoCRMWorkspaceId' => $workspaceId,
                'unreadCount>' => 0,
                'deleted' => false,
            ])
            ->count();

        $counts['pendingPayments'] = $this->entityManager
            ->getRDBRepository('Payment')
            ->where([
                'omniGoCRMWorkspaceId' => $workspaceId,
                'status' => ['Pending', 'Authorized', 'Partially Paid'],
                'deleted' => false,
            ])
            ->count();

        return [
            'workspaceId' => $workspaceId,
            'counts' => $counts,
            'generatedAt' => gmdate('Y-m-d H:i:s'),
        ];
    }
}
